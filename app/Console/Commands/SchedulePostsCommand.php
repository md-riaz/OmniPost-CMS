<?php

namespace App\Console\Commands;

use App\Jobs\PublishVariantJob;
use App\Models\PostVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SchedulePostsCommand extends Command
{
    protected $signature = 'posts:schedule';
    protected $description = 'Find and dispatch scheduled posts that are due for publishing';

    public function handle(): int
    {
        $this->info('Checking for scheduled posts...');

        // Find all post variants that are due
        $dueVariants = PostVariant::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with(['post', 'connectedSocialAccount'])
            ->get();

        if ($dueVariants->isEmpty()) {
            $this->info('No posts due for publishing.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($dueVariants as $variant) {
            try {
                // Dispatch the job first
                PublishVariantJob::dispatch($variant->id);
                
                // Only mark as publishing after successful dispatch
                $variant->update(['status' => 'publishing']);

                $count++;
                $this->info("Dispatched variant #{$variant->id} for publishing");

                Log::info('Scheduled post dispatched', [
                    'variant_id' => $variant->id,
                    'post_id' => $variant->post_id,
                    'platform' => $variant->platform,
                    'scheduled_at' => $variant->scheduled_at,
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to dispatch variant #{$variant->id}: {$e->getMessage()}");
                
                // Revert status back to scheduled so it can be retried
                $variant->update(['status' => 'scheduled']);
                
                Log::error('Failed to dispatch scheduled post', [
                    'variant_id' => $variant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Successfully dispatched {$count} post(s) for publishing.");

        return self::SUCCESS;
    }
}
