<?php

namespace App\Jobs;

use App\Services\MetricsService;
use App\Services\PlatformRateLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngestMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    public function __construct(
        private int $lookbackDays = 30
    ) {}

    public function handle(MetricsService $metricsService, PlatformRateLimiter $rateLimiter): void
    {
        Log::info('Starting metrics ingestion job', [
            'lookback_days' => $this->lookbackDays,
        ]);

        $variants = $metricsService->getVariantsToIngest($this->lookbackDays);
        
        $successCount = 0;
        $failureCount = 0;
        $skippedCount = 0;

        foreach ($variants as $variant) {
            try {
                // Check rate limiter before making API call
                $accountId = $variant->connected_social_account_id;
                if (!$rateLimiter->canMakeRequest($variant->platform, $accountId)) {
                    $waitTime = $rateLimiter->waitTime($variant->platform, $accountId);
                    Log::info('Rate limit reached, delaying metrics job', [
                        'platform' => $variant->platform,
                        'wait_time' => $waitTime,
                    ]);
                    $this->release($waitTime);
                    return;
                }

                $result = $metricsService->ingestMetricsForVariant($variant);
                
                if ($result) {
                    $successCount++;
                    $rateLimiter->recordRequest($variant->platform, $accountId);
                    $rateLimiter->recordSuccess($variant->platform, $accountId);
                } else {
                    $skippedCount++;
                }
                
                // Small delay between requests
                usleep(100000); // 100ms
                
            } catch (\Exception $e) {
                $failureCount++;
                $accountId = $variant->connected_social_account_id ?? null;
                
                if ($accountId) {
                    $rateLimiter->recordRequest($variant->platform, $accountId);
                    $rateLimiter->recordFailure($variant->platform, $accountId);
                }
                
                Log::error('Failed to ingest metrics for variant', [
                    'variant_id' => $variant->id,
                    'platform' => $variant->platform,
                    'error' => $e->getMessage(),
                ]);

                // If rate limited or circuit breaker is open, pause and retry later
                if (str_contains($e->getMessage(), 'Rate limited') || 
                    str_contains($e->getMessage(), '429') ||
                    ($accountId && $rateLimiter->isCircuitOpen($variant->platform, $accountId))) {
                    Log::warning('Rate limited or circuit open, stopping job to retry later');
                    $this->release(3600); // Retry in 1 hour
                    return;
                }
            }
        }

        Log::info('Metrics ingestion job completed', [
            'total_variants' => $variants->count(),
            'success' => $successCount,
            'failures' => $failureCount,
            'skipped' => $skippedCount,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Metrics ingestion job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
