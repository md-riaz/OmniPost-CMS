<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostStatusChange;
use App\Observers\PostObserver;
use App\Observers\PostCommentObserver;
use App\Observers\PostStatusChangeObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Post::observe(PostObserver::class);
        PostComment::observe(PostCommentObserver::class);
        PostStatusChange::observe(PostStatusChangeObserver::class);
    }
}
