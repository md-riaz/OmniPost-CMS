<?php

namespace App\Observers;

use App\Models\PostStatusChange;

class PostStatusChangeObserver
{
    /**
     * Handle the PostStatusChange "creating" event.
     *
     * Automatically set the changed_by field to the authenticated user
     * if it hasn't been explicitly set.
     */
    public function creating(PostStatusChange $change): void
    {
        if (empty($change->changed_by) && auth()->check()) {
            $change->changed_by = auth()->id();
        }
    }
}
