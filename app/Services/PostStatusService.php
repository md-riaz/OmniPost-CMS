<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\PostStatusChange;
use App\Models\AuditLog;
use App\Notifications\PostSubmittedForApproval;
use App\Notifications\PostApproved;
use App\Notifications\PostRejected;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostStatusService
{
    private const VALID_TRANSITIONS = [
        'draft' => ['pending'],
        'pending' => ['approved', 'draft'],
        'approved' => ['scheduled'],
        'scheduled' => ['publishing'],
        'publishing' => ['published', 'failed'],
        'failed' => ['scheduled', 'draft'],
    ];

    public function submitForApproval(Post $post, User $user): Post
    {
        if (!$this->canTransition($post, 'pending')) {
            throw new InvalidArgumentException("Cannot submit post in status '{$post->status}' for approval.");
        }

        return DB::transaction(function () use ($post, $user) {
            $oldStatus = $post->status;
            $post->status = 'pending';
            $post->approval_due_at = now()->addHours(4);
            $post->approval_escalated_at = null;
            $post->save();

            $this->recordTransition($post, $oldStatus, $user, null);

            // Notify approvers
            $approvers = User::whereHas('roles', function ($query) {
                $query->whereIn('slug', ['admin', 'approver']);
            })->get();

            foreach ($approvers as $approver) {
                $approver->notify(new PostSubmittedForApproval($post, $user));
            }

            return $post;
        });
    }

    public function approve(Post $post, User $user): Post
    {
        if (!$this->canTransition($post, 'approved')) {
            throw new InvalidArgumentException("Cannot approve post in status '{$post->status}'.");
        }

        return DB::transaction(function () use ($post, $user) {
            $oldStatus = $post->status;
            $post->status = 'approved';
            $post->approved_by = $user->id;
            $post->approved_at = now();
            $post->approval_due_at = null;
            $post->approval_escalated_at = null;
            $post->save();

            $this->recordTransition($post, $oldStatus, $user, null);

            // Audit log
            AuditLog::log('post_approved', $post, [
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'approved_by_id' => $user->id,
                'approved_by_name' => $user->name,
            ], $user->id);

            // Notify creator
            if ($post->creator) {
                $post->creator->notify(new PostApproved($post, $user));
            }

            return $post;
        });
    }

    public function reject(Post $post, User $user, string $reason): Post
    {
        if (!$this->canTransition($post, 'draft')) {
            throw new InvalidArgumentException("Cannot reject post in status '{$post->status}'.");
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException("Rejection reason is required.");
        }

        return DB::transaction(function () use ($post, $user, $reason) {
            $oldStatus = $post->status;
            $post->status = 'draft';
            $post->approval_due_at = null;
            $post->approval_escalated_at = null;
            $post->save();

            $this->recordTransition($post, $oldStatus, $user, $reason);

            // Audit log
            AuditLog::log('post_rejected', $post, [
                'old_status' => $oldStatus,
                'new_status' => 'draft',
                'rejected_by_id' => $user->id,
                'rejected_by_name' => $user->name,
                'reason' => $reason,
            ], $user->id);

            // Notify creator
            if ($post->creator) {
                $post->creator->notify(new PostRejected($post, $user, $reason));
            }

            return $post;
        });
    }

    public function transitionToScheduled(Post $post): Post
    {
        if (!$this->canTransition($post, 'scheduled')) {
            throw new InvalidArgumentException("Cannot transition post in status '{$post->status}' to scheduled.");
        }

        $oldStatus = $post->status;
        $post->status = 'scheduled';
        $post->save();

        $this->recordTransition($post, $oldStatus, null, 'Automatically transitioned when variants were scheduled');

        return $post;
    }

    public function transitionToPublishing(Post $post): Post
    {
        if (!$this->canTransition($post, 'publishing')) {
            throw new InvalidArgumentException("Cannot transition post in status '{$post->status}' to publishing.");
        }

        $oldStatus = $post->status;
        $post->status = 'publishing';
        $post->save();

        $this->recordTransition($post, $oldStatus, null, 'Automatically transitioned by scheduler');

        return $post;
    }

    public function transitionToPublished(Post $post): Post
    {
        if (!$this->canTransition($post, 'published')) {
            throw new InvalidArgumentException("Cannot transition post in status '{$post->status}' to published.");
        }

        $oldStatus = $post->status;
        $post->status = 'published';
        $post->save();

        $this->recordTransition($post, $oldStatus, null, 'All variants published successfully');

        return $post;
    }

    public function transitionToFailed(Post $post, string $reason): Post
    {
        if (!$this->canTransition($post, 'failed')) {
            throw new InvalidArgumentException("Cannot transition post in status '{$post->status}' to failed.");
        }

        $oldStatus = $post->status;
        $post->status = 'failed';
        $post->save();

        $this->recordTransition($post, $oldStatus, null, $reason);

        return $post;
    }

    private function canTransition(Post $post, string $newStatus): bool
    {
        $currentStatus = $post->status ?? 'draft';
        
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus]);
    }

    private function recordTransition(Post $post, string $oldStatus, ?User $user, ?string $reason): void
    {
        PostStatusChange::create([
            'post_id' => $post->id,
            'from_status' => $oldStatus,
            'to_status' => $post->status,
            'changed_by' => $user?->id,
            'reason' => $reason,
            'changed_at' => now(),
        ]);
    }
}
