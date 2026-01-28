<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Performance - {{ $post->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.analytics.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Analytics
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $post->title }}</h1>
            <p class="text-gray-600">Performance metrics for all variants</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" action="{{ route('dashboard.analytics.post-performance', $post) }}" class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Apply Filters
                </button>
            </form>
        </div>

        @if($variantMetrics->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-500">No metrics available for this post yet.</p>
            </div>
        @else
            <!-- Variant Performance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                @foreach($variantMetrics as $data)
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ ucfirst($data['variant']->platform) }}
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $data['variant']->platform == 'facebook' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $data['variant']->connectedSocialAccount->display_name }}
                            </span>
                        </div>

                        @if($data['latest_metrics'])
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Likes</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ number_format($data['latest_metrics']->likes) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Comments</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ number_format($data['latest_metrics']->comments) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Shares</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ number_format($data['latest_metrics']->shares) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Impressions</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ number_format($data['latest_metrics']->impressions) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Clicks</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ number_format($data['latest_metrics']->clicks) }}
                                    </span>
                                </div>
                                <div class="pt-3 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">Engagement Rate</span>
                                        <span class="text-xl font-bold text-purple-600">
                                            {{ number_format($data['avg_engagement_rate'] ?? 0, 2) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No metrics collected yet</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Historical Performance Chart -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Performance Over Time</h2>
                <canvas id="historicalChart" height="80"></canvas>
            </div>

            <!-- Comparison Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Platform Comparison</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Engagement</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Eng. Rate</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Best Day</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($variantMetrics->sortByDesc('total_engagement') as $data)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $data['variant']->platform == 'facebook' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                            {{ ucfirst($data['variant']->platform) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">
                                        {{ number_format($data['total_engagement']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ number_format($data['avg_engagement_rate'] ?? 0, 2) }}%
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ number_format($data['latest_metrics']->impressions ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600">
                                        {{ $data['historical_metrics']->sortByDesc('engagement_rate')->first()?->captured_at->format('M d') ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const variantData = @json($variantMetrics->values());
        
        if (variantData.length === 0) return;
        
        // Prepare datasets for each variant
        const datasets = variantData.map((data, index) => {
            const colors = [
                { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.1)' },
                { border: 'rgb(99, 102, 241)', bg: 'rgba(99, 102, 241, 0.1)' },
                { border: 'rgb(168, 85, 247)', bg: 'rgba(168, 85, 247, 0.1)' },
            ];
            const color = colors[index % colors.length];
            
            return {
                label: data.variant.platform.charAt(0).toUpperCase() + data.variant.platform.slice(1),
                data: data.historical_metrics.map(m => ({
                    x: m.captured_at,
                    y: m.likes + m.comments + m.shares
                })).sort((a, b) => new Date(a.x) - new Date(b.x)),
                borderColor: color.border,
                backgroundColor: color.bg,
                tension: 0.4,
                fill: true
            };
        });
        
        const ctx = document.getElementById('historicalChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM d'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
