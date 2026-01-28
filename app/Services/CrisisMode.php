<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

class CrisisMode
{
    private const CACHE_TTL = 86400; // 24 hours

    public function enableForBrand(int $brandId, ?string $platform = null, ?int $userId = null): void
    {
        $cacheKey = $this->getCacheKey($brandId, $platform);
        Cache::put($cacheKey, [
            'enabled_at' => now()->toIso8601String(),
            'enabled_by' => $userId,
            'platform' => $platform,
        ], now()->addSeconds(self::CACHE_TTL));

        $brand = Brand::find($brandId);
        if ($brand) {
            AuditLog::log(
                action: 'crisis_mode_enabled',
                entity: $brand,
                changes: ['platform' => $platform],
                userId: $userId
            );
        }
    }

    public function disable(int $brandId, ?string $platform = null, ?int $userId = null): void
    {
        $cacheKey = $this->getCacheKey($brandId, $platform);
        Cache::forget($cacheKey);

        // Also clear platform-specific cache if disabling all
        if ($platform === null) {
            Cache::forget($this->getCacheKey($brandId, 'facebook'));
            Cache::forget($this->getCacheKey($brandId, 'linkedin'));
        }

        $brand = Brand::find($brandId);
        if ($brand) {
            AuditLog::log(
                action: 'crisis_mode_disabled',
                entity: $brand,
                changes: ['platform' => $platform],
                userId: $userId
            );
        }
    }

    public function isActive(int $brandId, ?string $platform = null): bool
    {
        // Check brand-wide crisis mode
        $brandWideKey = $this->getCacheKey($brandId, null);
        if (Cache::has($brandWideKey)) {
            return true;
        }

        // Check platform-specific crisis mode
        if ($platform) {
            $platformKey = $this->getCacheKey($brandId, $platform);
            return Cache::has($platformKey);
        }

        return false;
    }

    public function getStatus(int $brandId): array
    {
        $status = [
            'enabled' => false,
            'platforms' => [],
        ];

        // Check brand-wide
        $brandWideKey = $this->getCacheKey($brandId, null);
        if (Cache::has($brandWideKey)) {
            $status['enabled'] = true;
            $status['scope'] = 'all';
            $status['details'] = Cache::get($brandWideKey);
            return $status;
        }

        // Check each platform
        foreach (['facebook', 'linkedin'] as $platform) {
            $platformKey = $this->getCacheKey($brandId, $platform);
            if (Cache::has($platformKey)) {
                $status['enabled'] = true;
                $status['platforms'][$platform] = Cache::get($platformKey);
            }
        }

        if (!empty($status['platforms'])) {
            $status['scope'] = 'platform';
        }

        return $status;
    }

    private function getCacheKey(int $brandId, ?string $platform = null): string
    {
        $base = "crisis_mode:brand:{$brandId}";
        return $platform ? "{$base}:{$platform}" : $base;
    }
}
