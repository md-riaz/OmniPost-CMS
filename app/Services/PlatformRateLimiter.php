<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PlatformRateLimiter
{
    private const FACEBOOK_HOURLY_LIMIT = 200;
    private const LINKEDIN_DAILY_LIMIT = 500;
    private const CIRCUIT_BREAKER_THRESHOLD = 5;
    private const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutes

    public function canMakeRequest(string $platform, ?int $accountId = null): bool
    {
        $key = $this->getKey($platform, $accountId);
        
        if ($this->isCircuitOpen($platform, $accountId)) {
            return false;
        }

        $usage = $this->getUsage($platform, $accountId);
        $limit = $this->getLimit($platform);

        return $usage < $limit;
    }

    public function recordRequest(string $platform, ?int $accountId = null): void
    {
        $key = $this->getKey($platform, $accountId);
        $ttl = $this->getTtl($platform);
        
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, $ttl);
    }

    public function recordFailure(string $platform, ?int $accountId = null): void
    {
        $key = $this->getFailureKey($platform, $accountId);
        $failures = Cache::get($key, 0) + 1;
        
        Cache::put($key, $failures, now()->addMinutes(60));
        
        if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $this->openCircuit($platform, $accountId);
        }
    }

    public function recordSuccess(string $platform, ?int $accountId = null): void
    {
        $key = $this->getFailureKey($platform, $accountId);
        Cache::forget($key);
        $this->closeCircuit($platform, $accountId);
    }

    public function waitTime(string $platform, ?int $accountId = null): int
    {
        if ($this->isCircuitOpen($platform, $accountId)) {
            $key = $this->getCircuitKey($platform, $accountId);
            $openedAt = Cache::get($key);
            if ($openedAt) {
                return max(0, self::CIRCUIT_BREAKER_TIMEOUT - (now()->timestamp - $openedAt));
            }
        }

        $usage = $this->getUsage($platform, $accountId);
        $limit = $this->getLimit($platform);

        if ($usage < $limit) {
            return 0;
        }

        // Calculate time until window resets
        $key = $this->getKey($platform, $accountId);
        $expiresAt = Cache::get($key . ':expires', now()->timestamp);
        return max(0, $expiresAt - now()->timestamp);
    }

    public function isCircuitOpen(string $platform, ?int $accountId = null): bool
    {
        $key = $this->getCircuitKey($platform, $accountId);
        return Cache::has($key);
    }

    public function getUsage(string $platform, ?int $accountId = null): int
    {
        $key = $this->getKey($platform, $accountId);
        return Cache::get($key, 0);
    }

    public function getRemainingCalls(string $platform, ?int $accountId = null): int
    {
        $usage = $this->getUsage($platform, $accountId);
        $limit = $this->getLimit($platform);
        return max(0, $limit - $usage);
    }

    private function openCircuit(string $platform, ?int $accountId = null): void
    {
        $key = $this->getCircuitKey($platform, $accountId);
        Cache::put($key, now()->timestamp, now()->addSeconds(self::CIRCUIT_BREAKER_TIMEOUT));
    }

    private function closeCircuit(string $platform, ?int $accountId = null): void
    {
        $key = $this->getCircuitKey($platform, $accountId);
        Cache::forget($key);
    }

    private function getKey(string $platform, ?int $accountId = null): string
    {
        $base = "rate_limit:{$platform}";
        return $accountId ? "{$base}:{$accountId}" : $base;
    }

    private function getFailureKey(string $platform, ?int $accountId = null): string
    {
        $base = "rate_limit_failures:{$platform}";
        return $accountId ? "{$base}:{$accountId}" : $base;
    }

    private function getCircuitKey(string $platform, ?int $accountId = null): string
    {
        $base = "circuit_breaker:{$platform}";
        return $accountId ? "{$base}:{$accountId}" : $base;
    }

    private function getLimit(string $platform): int
    {
        return match (strtolower($platform)) {
            'facebook' => self::FACEBOOK_HOURLY_LIMIT,
            'linkedin' => self::LINKEDIN_DAILY_LIMIT,
            default => 100,
        };
    }

    private function getTtl(string $platform): Carbon
    {
        return match (strtolower($platform)) {
            'facebook' => now()->addHour(),
            'linkedin' => now()->addDay(),
            default => now()->addHour(),
        };
    }
}
