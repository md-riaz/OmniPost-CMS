<?php

namespace App\Observers;

use App\Models\Post;

class PostObserver
{
    /**
     * Handle the Post "creating" event.
     *
     * Automatically set the created_by field to the authenticated user
     * if it hasn't been explicitly set.
     */
    public function creating(Post $post): void
    {
        if (empty($post->created_by) && auth()->check()) {
            $post->created_by = auth()->id();
        }
    }
}
