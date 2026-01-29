<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'approver']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->hasRole(['admin', 'editor', 'approver']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // Admins can update any post
        if ($user->hasRole('admin')) {
            return true;
        }

        // Editors can update their own drafts
        if ($user->hasRole('editor') && $post->created_by === $user->id && $post->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        // Only admins or the creator can delete drafts
        if ($user->hasRole('admin')) {
            return true;
        }

        return $post->created_by === $user->id && $post->status === 'draft';
    }

    /**
     * Determine whether the user can submit for approval.
     */
    public function submitForApproval(User $user, Post $post): bool
    {
        // Only creator or admin can submit
        return ($post->created_by === $user->id || $user->hasRole('admin')) && $post->status === 'draft';
    }

    /**
     * Determine whether the user can approve the post.
     */
    public function approve(User $user, Post $post): bool
    {
        // Only approvers and admins can approve
        return $user->hasRole(['admin', 'approver']) && $post->status === 'pending';
    }

    /**
     * Determine whether the user can reject the post.
     */
    public function reject(User $user, Post $post): bool
    {
        // Only approvers and admins can reject
        return $user->hasRole(['admin', 'approver']) && $post->status === 'pending';
    }

    /**
     * Determine whether the user can comment on the post.
     */
    public function comment(User $user, Post $post): bool
    {
        // Anyone who can view can comment
        return $user->hasRole(['admin', 'editor', 'approver']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
