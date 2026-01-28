<?php

namespace App\Jobs;

use App\Contracts\PlatformConnector;
use App\Models\PostVariant;
use App\Models\PublicationAttempt;
use App\Models\AuditLog;
use App\Services\Platforms\FacebookConnector;
use App\Services\Platforms\LinkedInConnector;
use App\Services\PlatformRateLimiter;
use App\Services\CrisisMode;
use App\Notifications\AdminAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PublishVariantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    public $timeout = 120;

    public function __construct(
        public int $variantId,
        public int $attemptNumber = 1
    ) {}

    public function handle(PlatformRateLimiter $rateLimiter, CrisisMode $crisisMode): void
    {
        $variant = PostVariant::with([
            'post',
            'connectedSocialAccount.token',
            'connectedSocialAccount.brand'
        ])->find($this->variantId);

        if (!$variant) {
            Log::error('PostVariant not found', ['variant_id' => $this->variantId]);
            return;
        }

        // Check crisis mode
        $brandId = $variant->connectedSocialAccount->brand_id;
        if ($crisisMode->isActive($brandId, $variant->platform)) {
            Log::warning('Crisis mode active, skipping publication', [
                'variant_id' => $this->variantId,
                'brand_id' => $brandId,
                'platform' => $variant->platform,
            ]);
            return;
        }

        // Generate idempotency key
        $idempotencyKey = hash('sha256', "variant:{$variant->id}:attempt:{$this->attemptNumber}:" . now()->timestamp);

        // Check if already published (idempotency)
        $existingAttempt = PublicationAttempt::where('post_variant_id', $variant->id)
            ->where('result', 'success')
            ->whereNotNull('external_post_id')
            ->first();

        if ($existingAttempt) {
            Log::warning('PostVariant already published, skipping', [
                'variant_id' => $this->variantId,
                'external_post_id' => $existingAttempt->external_post_id,
            ]);
            return;
        }

        // Check rate limiter
        $accountId = $variant->connectedSocialAccount->id;
        if (!$rateLimiter->canMakeRequest($variant->platform, $accountId)) {
            $waitTime = $rateLimiter->waitTime($variant->platform, $accountId);
            Log::info('Rate limit reached, delaying job', [
                'platform' => $variant->platform,
                'wait_time' => $waitTime,
            ]);
            $this->release($waitTime);
            return;
        }

        // Create publication attempt record
        $attempt = PublicationAttempt::create([
            'post_variant_id' => $variant->id,
            'attempt_no' => $this->attemptNumber,
            'idempotency_key' => $idempotencyKey,
            'queued_at' => now(),
            'started_at' => now(),
        ]);

        try {
            // Validate connected account
            $account = $variant->connectedSocialAccount;
            if (!$account || !$account->token) {
                throw new \Exception('Connected social account or token not found');
            }

            // Get the appropriate connector
            $connector = $this->getConnector($variant->platform);

            // Refresh token if needed
            try {
                $token = $connector->refreshTokenIfNeeded($account->token);
            } catch (\Exception $e) {
                // Token refresh failed - mark account as expired
                $account->update(['status' => 'expired']);
                $rateLimiter->recordFailure($variant->platform, $accountId);
                
                // Notify admins
                $this->notifyAdmins(
                    AdminAlert::tokenRefreshFailed($variant->platform, $account->account_name)
                );
                
                throw new \Exception('Token expired and could not be refreshed: ' . $e->getMessage(), 401);
            }

            // Get the text to publish
            $text = $variant->text_override ?: $variant->post->base_text;

            // Get publish target ID and access token
            $targetId = $account->external_account_id;
            $accessToken = $token->access_token;

            // For Facebook, use page access token if available
            if ($variant->platform === 'facebook' && isset($account->meta['page_access_token'])) {
                $accessToken = $account->meta['page_access_token'];
            }

            // Prepare options
            $options = [];
            if ($variant->post->target_url) {
                $options['link'] = $variant->post->target_url;
            }

            // Publish to platform
            $result = $connector->publish($targetId, $text, $accessToken, $options);

            // Record successful API call
            $rateLimiter->recordRequest($variant->platform, $accountId);
            $rateLimiter->recordSuccess($variant->platform, $accountId);

            // Success - update attempt and variant
            $attempt->update([
                'finished_at' => now(),
                'result' => 'success',
                'external_post_id' => $result['external_post_id'],
                'raw_response' => $result['raw_response'],
            ]);

            $variant->update([
                'status' => 'published',
            ]);

            // Audit log
            AuditLog::log('post_published', $variant, [
                'platform' => $variant->platform,
                'external_post_id' => $result['external_post_id'],
            ]);

            Log::info('PostVariant published successfully', [
                'variant_id' => $variant->id,
                'platform' => $variant->platform,
                'external_post_id' => $result['external_post_id'],
            ]);

        } catch (\Exception $e) {
            // Record failed API call
            $rateLimiter->recordRequest($variant->platform, $accountId);
            $rateLimiter->recordFailure($variant->platform, $accountId);
            
            // Failure - update attempt
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            $attempt->update([
                'finished_at' => now(),
                'result' => 'fail',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            Log::error('Failed to publish PostVariant', [
                'variant_id' => $variant->id,
                'attempt_no' => $this->attemptNumber,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            // Handle different error types
            if ($errorCode == 190 || $errorCode == 401) {
                // Token expired - don't retry
                $variant->update(['status' => 'failed']);
                Log::warning('Token expired, marking variant as failed without retry', [
                    'variant_id' => $variant->id,
                ]);
                return;
            }

            if ($errorCode == 613 || $errorCode == 429) {
                // Rate limited - will retry with backoff
                Log::info('Rate limited, will retry with backoff', [
                    'variant_id' => $variant->id,
                    'attempt_no' => $this->attemptNumber,
                ]);
            }

            // Check if we've exhausted retries
            if ($this->attempts() >= $this->tries) {
                $variant->update(['status' => 'failed']);
                Log::error('Max retries exhausted, marking variant as failed', [
                    'variant_id' => $variant->id,
                ]);
                
                // Check for multiple failures and send alert
                $this->checkFailureThreshold();
            } else {
                // Will be retried automatically by Laravel's queue
                $variant->update(['status' => 'publishing']);
            }

            // Re-throw to trigger Laravel's retry mechanism
            throw $e;
        }
    }

    private function getConnector(string $platform): PlatformConnector
    {
        return match($platform) {
            'facebook' => app(FacebookConnector::class),
            'linkedin' => app(LinkedInConnector::class),
            default => throw new \Exception('Unsupported platform: ' . $platform),
        };
    }

    private function checkFailureThreshold(): void
    {
        $threshold = config('omnipost.alert_threshold_failed_jobs', 5);
        $oneHourAgo = now()->subHour();

        $recentFailures = PublicationAttempt::where('result', 'fail')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        if ($recentFailures >= $threshold) {
            $this->notifyAdmins(AdminAlert::failedPublishes($recentFailures, '1 hour'));
        }
    }

    private function notifyAdmins($alert): void
    {
        $admins = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('slug', 'admin');
        })->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $alert);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishVariantJob failed permanently', [
            'variant_id' => $this->variantId,
            'exception' => $exception->getMessage(),
        ]);

        $variant = PostVariant::with('post.creator')->find($this->variantId);
        if ($variant) {
            $variant->update(['status' => 'failed']);

            // Notify admins and post creator
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('slug', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\PublishingFailed($variant, $exception->getMessage()));
            }

            if ($variant->post && $variant->post->creator) {
                $variant->post->creator->notify(new \App\Notifications\PublishingFailed($variant, $exception->getMessage()));
            }
        }
    }
}
