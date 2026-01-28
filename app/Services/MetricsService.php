<?php

namespace App\Services;

use App\Models\MetricsSnapshot;
use App\Models\PostVariant;
use App\Models\PublicationAttempt;
use App\Services\Platforms\FacebookConnector;
use App\Services\Platforms\LinkedInConnector;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MetricsService
{
    public function __construct(
        private FacebookConnector $facebookConnector,
        private LinkedInConnector $linkedInConnector
    ) {}

    public function ingestMetricsForVariant(PostVariant $variant): bool
    {
        // Find the successful publication attempt
        $attempt = $variant->publicationAttempts()
            ->where('result', 'success')
            ->whereNotNull('external_post_id')
            ->latest()
            ->first();

        if (!$attempt) {
            Log::warning('No successful publication attempt found for variant', [
                'variant_id' => $variant->id,
            ]);
            return false;
        }

        // Get the connected account and token
        $account = $variant->connectedSocialAccount;
        if (!$account) {
            Log::warning('No connected account found for variant', [
                'variant_id' => $variant->id,
            ]);
            return false;
        }

        $token = $account->oauthToken;
        if (!$token || $token->isExpired()) {
            Log::warning('No valid token found for variant', [
                'variant_id' => $variant->id,
                'account_id' => $account->id,
            ]);
            return false;
        }

        // Fetch metrics based on platform
        $result = match ($variant->platform) {
            'facebook' => $this->fetchFacebookMetrics($attempt->external_post_id, $account),
            'linkedin' => $this->fetchLinkedInMetrics($attempt->external_post_id, $token->access_token),
            default => ['success' => false, 'error' => 'Unsupported platform'],
        };

        if (!$result['success']) {
            Log::error('Failed to fetch metrics for variant', [
                'variant_id' => $variant->id,
                'platform' => $variant->platform,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            return false;
        }

        // Store metrics snapshot
        $this->storeMetricsSnapshot($variant, $result['metrics'], $result['raw_data'] ?? []);

        return true;
    }

    private function fetchFacebookMetrics(string $postId, $account): array
    {
        // Use page access token from account meta
        $pageAccessToken = $account->meta['page_access_token'] ?? null;
        
        if (!$pageAccessToken) {
            return [
                'success' => false,
                'error' => 'No page access token available',
            ];
        }

        return $this->facebookConnector->fetchMetrics($postId, $pageAccessToken);
    }

    private function fetchLinkedInMetrics(string $shareUrn, string $accessToken): array
    {
        return $this->linkedInConnector->fetchMetrics($shareUrn, $accessToken);
    }

    private function storeMetricsSnapshot(PostVariant $variant, array $metrics, array $rawData = []): void
    {
        MetricsSnapshot::updateOrCreate(
            [
                'post_variant_id' => $variant->id,
                'captured_at' => Carbon::today(),
            ],
            [
                'likes' => $metrics['likes'] ?? 0,
                'comments' => $metrics['comments'] ?? 0,
                'shares' => $metrics['shares'] ?? 0,
                'impressions' => $metrics['impressions'] ?? 0,
                'clicks' => $metrics['clicks'] ?? 0,
                'raw_metrics' => $rawData,
            ]
        );

        Log::info('Stored metrics snapshot', [
            'variant_id' => $variant->id,
            'metrics' => $metrics,
        ]);
    }

    public function getVariantsToIngest(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return PostVariant::query()
            ->whereHas('publicationAttempts', function ($query) {
                $query->where('result', 'success')
                    ->whereNotNull('external_post_id');
            })
            ->where('scheduled_at', '>=', now()->subDays($days))
            ->with(['connectedSocialAccount.oauthToken', 'publicationAttempts'])
            ->get();
    }

    public function getMetricsForPost(int $postId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = MetricsSnapshot::query()
            ->whereHas('postVariant', function ($q) use ($postId) {
                $q->where('post_id', $postId);
            })
            ->with(['postVariant.connectedSocialAccount']);

        if ($dateFrom) {
            $query->where('captured_at', '>=', Carbon::parse($dateFrom));
        }

        if ($dateTo) {
            $query->where('captured_at', '<=', Carbon::parse($dateTo));
        }

        return $query->orderBy('captured_at', 'desc')->get();
    }

    public function getAggregatedMetrics(?int $brandId = null, ?string $platform = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = MetricsSnapshot::query()
            ->with(['postVariant.post.brand', 'postVariant.connectedSocialAccount']);

        if ($brandId) {
            $query->whereHas('postVariant.post', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
        }

        if ($platform) {
            $query->whereHas('postVariant', function ($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        if ($dateFrom) {
            $query->where('captured_at', '>=', Carbon::parse($dateFrom));
        }

        if ($dateTo) {
            $query->where('captured_at', '<=', Carbon::parse($dateTo));
        }

        return $query->orderBy('captured_at', 'desc')->get();
    }
}
