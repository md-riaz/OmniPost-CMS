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
        return $user->hasPrivilege('post.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->hasPrivilege('post.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPrivilege('post.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // Users with post.manage privilege can update any post
        if ($user->hasPrivilege('post.manage')) {
            return true;
        }

        // Users with post.create can update their own drafts
        if ($user->hasPrivilege('post.create') && $post->created_by === $user->id && $post->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        // Users with post.manage can delete any post
        if ($user->hasPrivilege('post.manage')) {
            return true;
        }

        // Users can delete their own drafts
        return $user->hasPrivilege('post.create') && $post->created_by === $user->id && $post->status === 'draft';
    }

    /**
     * Determine whether the user can submit for approval.
     */
    public function submitForApproval(User $user, Post $post): bool
    {
        // Only creator or users with post.manage privilege can submit
        return ($post->created_by === $user->id || $user->hasPrivilege('post.manage')) && $post->status === 'draft';
    }

    /**
     * Determine whether the user can approve the post.
     */
    public function approve(User $user, Post $post): bool
    {
        // Only users with post.approve privilege can approve
        return $user->hasPrivilege('post.approve') && $post->status === 'pending';
    }

    /**
     * Determine whether the user can reject the post.
     */
    public function reject(User $user, Post $post): bool
    {
        // Only users with post.approve privilege can reject
        return $user->hasPrivilege('post.approve') && $post->status === 'pending';
    }

    /**
     * Determine whether the user can comment on the post.
     */
    public function comment(User $user, Post $post): bool
    {
        // Anyone who can view posts can comment
        return $user->hasPrivilege('post.view');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->hasPrivilege('post.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasPrivilege('post.manage');
    }
}
