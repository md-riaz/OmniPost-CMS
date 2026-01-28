<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PostVariant::query()
            ->with(['post.brand', 'connectedSocialAccount'])
            ->whereNotNull('scheduled_at')
            ->whereIn('status', ['scheduled', 'publishing', 'published', 'failed']);

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->whereHas('post', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        // Filter by platform
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start') && $request->has('end')) {
            $query->whereBetween('scheduled_at', [
                $request->start,
                $request->end
            ]);
        }

        $variants = $query->orderBy('scheduled_at')->get();

        // Transform to FullCalendar format
        $events = $variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'title' => $variant->post->title,
                'start' => $variant->scheduled_at->toIso8601String(),
                'platform' => $variant->platform,
                'status' => $variant->status,
                'brand' => $variant->post->brand->name,
                'url' => route('tyro-dashboard.resources.show', [
                    'resource' => 'post-variants',
                    'id' => $variant->id
                ]),
                'backgroundColor' => $this->getBackgroundColor($variant->platform, $variant->status),
                'borderColor' => $this->getBorderColor($variant->status),
                'extendedProps' => [
                    'post_id' => $variant->post_id,
                    'account' => $variant->connectedSocialAccount->display_name ?? 'N/A',
                ],
            ];
        });

        return response()->json([
            'events' => $events
        ]);
    }

    public function updateSchedule(Request $request, PostVariant $variant): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date',
        ]);

        if (!in_array($variant->status, ['scheduled', 'draft', 'failed'])) {
            return response()->json([
                'error' => 'Cannot reschedule variant in current status'
            ], 422);
        }

        $variant->scheduled_at = $request->scheduled_at;
        $variant->save();

        return response()->json([
            'success' => true,
            'message' => 'Variant rescheduled successfully',
            'variant' => $variant
        ]);
    }

    private function getBackgroundColor(string $platform, string $status): string
    {
        // Platform colors
        $platformColors = [
            'facebook' => '#1877F2',
            'linkedin' => '#0A66C2',
        ];

        // If failed, show red
        if ($status === 'failed') {
            return '#DC2626';
        }

        // If published, lighten the color
        if ($status === 'published') {
            return match($platform) {
                'facebook' => '#60A5FA',
                'linkedin' => '#3B82F6',
                default => '#94A3B8',
            };
        }

        return $platformColors[$platform] ?? '#64748B';
    }

    private function getBorderColor(string $status): string
    {
        return match($status) {
            'failed' => '#991B1B',
            'published' => '#059669',
            'publishing' => '#D97706',
            default => '#475569',
        };
    }
}
