<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\OAuthToken;
use Carbon\Carbon;

class HealthCheckController extends Controller
{
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'checks' => [
                'database' => $this->checkDatabase(),
                'queue' => $this->checkQueue(),
                'disk_space' => $this->checkDiskSpace(),
                'cache' => $this->checkCache(),
                'tokens' => $this->checkTokens(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $allHealthy = collect($health['checks'])->every(fn($check) => $check['status'] === 'ok');
        
        if (!$allHealthy) {
            $health['status'] = 'unhealthy';
        }

        return response()->json($health, $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $result = DB::select('SELECT 1 as result');
            
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $pendingJobs = DB::table($jobsTable)->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'ok';
            if ($pendingJobs > 100) {
                $status = 'warning';
            }
            if ($pendingJobs > 500 || $failedJobs > 10) {
                $status = 'error';
            }

            return [
                'status' => $status,
                'message' => "Queue operational",
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkDiskSpace(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercent = 100 - (($freeSpace / $totalSpace) * 100);

            $status = 'ok';
            if ($usedPercent > 80) {
                $status = 'warning';
            }
            if ($usedPercent > 90) {
                $status = 'error';
            }

            return [
                'status' => $status,
                'message' => 'Disk space check completed',
                'free_space_mb' => round($freeSpace / 1024 / 1024, 2),
                'used_percent' => round($usedPercent, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Disk space check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test';
            
            Cache::put($testKey, $testValue, now()->addMinutes(1));
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache is working',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkTokens(): array
    {
        try {
            $expiringTokens = OAuthToken::where('expires_at', '<=', now()->addDays(7))
                ->where('expires_at', '>', now())
                ->count();

            $expiredTokens = OAuthToken::where('expires_at', '<=', now())->count();

            $status = 'ok';
            if ($expiringTokens > 0) {
                $status = 'warning';
            }
            if ($expiredTokens > 0) {
                $status = 'error';
            }

            return [
                'status' => $status,
                'message' => 'Token expiry check completed',
                'expiring_soon' => $expiringTokens,
                'expired' => $expiredTokens,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Token check failed: ' . $e->getMessage(),
            ];
        }
    }
}
