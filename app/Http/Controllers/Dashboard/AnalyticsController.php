<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Post;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(
        private MetricsService $metricsService
    ) {}

    public function index(Request $request)
    {
        $brandId = $request->input('brand_id');
        $platform = $request->input('platform');
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Cache key based on filters
        $cacheKey = "analytics_dashboard_{$brandId}_{$platform}_{$dateFrom}_{$dateTo}";

        $data = Cache::remember($cacheKey, 3600, function () use ($brandId, $platform, $dateFrom, $dateTo) {
            $metrics = $this->metricsService->getAggregatedMetrics($brandId, $platform, $dateFrom, $dateTo);

            return [
                'total_posts' => $metrics->groupBy('post_variant_id')->count(),
                'total_engagement' => $metrics->sum(fn($m) => $m->getTotalEngagement()),
                'total_impressions' => $metrics->sum('impressions'),
                'total_clicks' => $metrics->sum('clicks'),
                'avg_engagement_rate' => $metrics->avg('engagement_rate'),
                'avg_ctr' => $metrics->avg('click_through_rate'),
                'metrics' => $metrics,
                'engagement_over_time' => $this->getEngagementOverTime($metrics),
                'platform_comparison' => $this->getPlatformComparison($metrics),
                'top_posts' => $this->getTopPosts($metrics),
                'best_posting_times' => $this->getBestPostingTimes($metrics),
            ];
        });

        $brands = Brand::all();

        return view('dashboard.analytics.index', array_merge($data, [
            'brands' => $brands,
            'filters' => [
                'brand_id' => $brandId,
                'platform' => $platform,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]));
    }

    public function postPerformance(Request $request, Post $post)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $metrics = $this->metricsService->getMetricsForPost($post->id, $dateFrom, $dateTo);

        // Group by variant
        $variantMetrics = $metrics->groupBy('post_variant_id')->map(function ($variantMetrics) {
            return [
                'variant' => $variantMetrics->first()->postVariant,
                'latest_metrics' => $variantMetrics->first(),
                'historical_metrics' => $variantMetrics,
                'total_engagement' => $variantMetrics->sum(fn($m) => $m->getTotalEngagement()),
                'avg_engagement_rate' => $variantMetrics->avg('engagement_rate'),
            ];
        });

        return view('dashboard.analytics.post-performance', [
            'post' => $post,
            'variantMetrics' => $variantMetrics,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $brandId = $request->input('brand_id');
        $platform = $request->input('platform');
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $metrics = $this->metricsService->getAggregatedMetrics($brandId, $platform, $dateFrom, $dateTo);

        // Generate CSV
        $filename = 'metrics_export_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($metrics) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Date',
                'Post ID',
                'Post Title',
                'Brand',
                'Platform',
                'Account',
                'Likes',
                'Comments',
                'Shares',
                'Impressions',
                'Clicks',
                'Engagement Rate (%)',
                'CTR (%)',
            ]);

            // Data rows
            foreach ($metrics as $metric) {
                fputcsv($file, [
                    $metric->captured_at->format('Y-m-d'),
                    $metric->postVariant->post_id,
                    $metric->postVariant->post->title ?? 'N/A',
                    $metric->postVariant->post->brand->name ?? 'N/A',
                    $metric->postVariant->platform,
                    $metric->postVariant->connectedSocialAccount->display_name ?? 'N/A',
                    $metric->likes,
                    $metric->comments,
                    $metric->shares,
                    $metric->impressions,
                    $metric->clicks,
                    $metric->engagement_rate ?? 'N/A',
                    $metric->click_through_rate ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getEngagementOverTime($metrics)
    {
        return $metrics->groupBy(fn($m) => $m->captured_at->format('Y-m-d'))
            ->map(function ($dayMetrics) {
                return [
                    'date' => $dayMetrics->first()->captured_at->format('Y-m-d'),
                    'engagement' => $dayMetrics->sum(fn($m) => $m->getTotalEngagement()),
                    'impressions' => $dayMetrics->sum('impressions'),
                    'clicks' => $dayMetrics->sum('clicks'),
                ];
            })
            ->sortBy('date')
            ->values();
    }

    private function getPlatformComparison($metrics)
    {
        return $metrics->groupBy(fn($m) => $m->postVariant->platform)
            ->map(function ($platformMetrics, $platform) {
                return [
                    'platform' => $platform,
                    'posts' => $platformMetrics->groupBy('post_variant_id')->count(),
                    'engagement' => $platformMetrics->sum(fn($m) => $m->getTotalEngagement()),
                    'impressions' => $platformMetrics->sum('impressions'),
                    'avg_engagement_rate' => round($platformMetrics->avg('engagement_rate'), 2),
                ];
            })
            ->values();
    }

    private function getTopPosts($metrics, int $limit = 10)
    {
        return $metrics->groupBy('post_variant_id')
            ->map(function ($variantMetrics) {
                $variant = $variantMetrics->first()->postVariant;
                $latestMetric = $variantMetrics->first();
                
                return [
                    'variant_id' => $variant->id,
                    'post_id' => $variant->post_id,
                    'post_title' => $variant->post->title ?? 'Untitled',
                    'platform' => $variant->platform,
                    'engagement' => $variantMetrics->sum(fn($m) => $m->getTotalEngagement()),
                    'impressions' => $latestMetric->impressions,
                    'engagement_rate' => $latestMetric->engagement_rate,
                ];
            })
            ->sortByDesc('engagement')
            ->take($limit)
            ->values();
    }

    private function getBestPostingTimes($metrics)
    {
        // Group by day of week and hour
        $timeData = $metrics->map(function ($metric) {
            $variant = $metric->postVariant;
            if (!$variant->scheduled_at) {
                return null;
            }

            return [
                'day' => $variant->scheduled_at->dayOfWeek,
                'hour' => $variant->scheduled_at->hour,
                'engagement_rate' => $metric->engagement_rate ?? 0,
            ];
        })->filter();

        $heatmapData = [];
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $entries = $timeData->filter(fn($d) => $d['day'] == $day && $d['hour'] == $hour);
                $heatmapData[] = [
                    'day' => $day,
                    'hour' => $hour,
                    'avg_engagement_rate' => $entries->avg('engagement_rate') ?? 0,
                    'count' => $entries->count(),
                ];
            }
        }

        return collect($heatmapData)->filter(fn($d) => $d['count'] > 0)->values();
    }
}
