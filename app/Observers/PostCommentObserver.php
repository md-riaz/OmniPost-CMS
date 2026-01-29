<?php

namespace App\Observers;

use App\Models\PostComment;

class PostCommentObserver
{
    /**
     * Handle the PostComment "creating" event.
     *
     * Automatically set the user_id field to the authenticated user
     * if it hasn't been explicitly set.
     */
    public function creating(PostComment $comment): void
    {
        if (empty($comment->user_id) && auth()->check()) {
            $comment->user_id = auth()->id();
        }
    }
}
