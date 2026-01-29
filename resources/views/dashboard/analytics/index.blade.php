<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - OmniPost</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Analytics Dashboard</h1>
            <p class="text-gray-600">Track your social media performance across all platforms</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" action="{{ route('dashboard.analytics.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <select name="brand_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ $filters['brand_id'] == $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                    <select name="platform" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Platforms</option>
                        <option value="facebook" {{ $filters['platform'] == 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="linkedin" {{ $filters['platform'] == 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('dashboard.analytics.export', request()->query()) }}" 
                       class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Posts</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($total_posts) }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Engagement</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($total_engagement) }}</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Impressions</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($total_impressions) }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avg Engagement Rate</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($avg_engagement_rate ?? 0, 2) }}%</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @include('dashboard.analytics.partials.engagement-chart', ['data' => $engagement_over_time])
            @include('dashboard.analytics.partials.platform-comparison', ['data' => $platform_comparison])
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 gap-6 mb-6">
            @include('dashboard.analytics.partials.best-times', ['data' => $best_posting_times])
        </div>

        <!-- Top Posts -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Top Performing Posts</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Engagement</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Impressions</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Eng. Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($top_posts as $post)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('dashboard.analytics.post-performance', $post['post_id']) }}" 
                                       class="text-blue-600 hover:text-blue-800">
                                        {{ $post['post_title'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $post['platform'] == 'facebook' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        {{ ucfirst($post['platform']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($post['engagement']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($post['impressions']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($post['engagement_rate'] ?? 0, 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    No posts found for the selected filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
