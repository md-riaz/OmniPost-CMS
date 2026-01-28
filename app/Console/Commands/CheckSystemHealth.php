<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\PublicationAttempt;
use App\Notifications\AdminAlert;

class CheckSystemHealth extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Check system health and send alerts if issues detected';

    public function handle(): int
    {
        $this->info('Running system health checks...');

        $this->checkFailedPublishes();
        $this->checkQueueDepth();
        $this->checkDiskSpace();

        $this->info('Health check completed.');
        return 0;
    }

    private function checkFailedPublishes(): void
    {
        $threshold = config('omnipost.alert_threshold_failed_jobs', 5);
        $oneHourAgo = now()->subHour();

        $failedCount = PublicationAttempt::where('result', 'failed')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        if ($failedCount >= $threshold) {
            $this->warn("⚠️  {$failedCount} failed publishes in last hour (threshold: {$threshold})");
            $this->notifyAdmins(AdminAlert::failedPublishes($failedCount, '1 hour'));
        } else {
            $this->info("✓ Failed publishes: {$failedCount}/{$threshold}");
        }
    }

    private function checkQueueDepth(): void
    {
        $threshold = config('omnipost.alert_threshold_queue_depth', 100);
        $jobsTable = config('queue.connections.database.table', 'jobs');
        
        $queueDepth = DB::table($jobsTable)->count();

        if ($queueDepth > $threshold) {
            $this->warn("⚠️  Queue depth is {$queueDepth} (threshold: {$threshold})");
            $this->notifyAdmins(AdminAlert::queueDepthHigh($queueDepth));
        } else {
            $this->info("✓ Queue depth: {$queueDepth}/{$threshold}");
        }
    }

    private function checkDiskSpace(): void
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usedPercent = 100 - (($freeSpace / $totalSpace) * 100);
        $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);

        if ($usedPercent > 90) {
            $this->error("⚠️  Disk space critical: {$usedPercent}% used");
            $this->notifyAdmins(AdminAlert::diskSpaceLow($freeSpaceMB, $usedPercent));
        } elseif ($usedPercent > 80) {
            $this->warn("⚠️  Disk space low: {$usedPercent}% used");
        } else {
            $this->info("✓ Disk space: {$usedPercent}% used ({$freeSpaceMB} MB free)");
        }
    }

    private function notifyAdmins(AdminAlert $alert): void
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to notify');
            return;
        }

        Notification::send($admins, $alert);
    }
}
