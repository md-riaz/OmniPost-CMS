<?php

namespace App\Jobs;

use App\Contracts\PlatformConnector;
use App\Models\PostVariant;
use App\Models\PublicationAttempt;
use App\Services\Platforms\FacebookConnector;
use App\Services\Platforms\LinkedInConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    public function handle(): void
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

        // Check if already published (idempotency)
        $lastAttempt = $variant->publicationAttempts()
            ->where('result', 'success')
            ->whereNotNull('external_post_id')
            ->first();

        if ($lastAttempt) {
            Log::warning('PostVariant already published, skipping', [
                'variant_id' => $this->variantId,
                'external_post_id' => $lastAttempt->external_post_id,
            ]);
            return;
        }

        // Create publication attempt record
        $attempt = PublicationAttempt::create([
            'post_variant_id' => $variant->id,
            'attempt_no' => $this->attemptNumber,
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

            Log::info('PostVariant published successfully', [
                'variant_id' => $variant->id,
                'platform' => $variant->platform,
                'external_post_id' => $result['external_post_id'],
            ]);

        } catch (\Exception $e) {
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

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishVariantJob failed permanently', [
            'variant_id' => $this->variantId,
            'exception' => $exception->getMessage(),
        ]);

        $variant = PostVariant::find($this->variantId);
        if ($variant) {
            $variant->update(['status' => 'failed']);
        }
    }
}
