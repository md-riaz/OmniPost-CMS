<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Console\Command;

class EscalateOverdueApprovals extends Command
{
    protected $signature = 'approvals:escalate-overdue';

    protected $description = 'Escalate pending posts that missed approval SLA';

    public function handle(): int
    {
        $posts = Post::query()
            ->where('status', 'pending')
            ->whereNotNull('approval_due_at')
            ->where('approval_due_at', '<=', now())
            ->whereNull('approval_escalated_at')
            ->with(['brand', 'campaign'])
            ->limit(200)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No overdue approvals found.');
            return self::SUCCESS;
        }

        $recipients = User::query()->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['admin', 'approver', 'manager']);
        })->get();

        foreach ($posts as $post) {
            foreach ($recipients as $recipient) {
                $recipient->notify(new AdminAlert(
                    title: 'Approval SLA Overdue',
                    message: "Post #{$post->id} is overdue for approval.",
                    severity: 'warning',
                    context: [
                        'post_id' => $post->id,
                        'post_title' => $post->title,
                        'brand' => $post->brand?->name,
                        'campaign' => $post->campaign?->name,
                        'approval_due_at' => optional($post->approval_due_at)->toDateTimeString(),
                    ]
                ));
            }

            $post->approval_escalated_at = now();
            $post->save();
        }

        $this->info("Escalated {$posts->count()} overdue post(s).");

        return self::SUCCESS;
    }
}
