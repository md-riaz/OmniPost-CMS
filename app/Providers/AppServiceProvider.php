<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\ConnectedSocialAccount;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostStatusChange;
use App\Models\PostVariant;
use App\Observers\PostObserver;
use App\Observers\PostCommentObserver;
use App\Observers\PostStatusChangeObserver;
use App\Policies\BrandPolicy;
use App\Policies\ConnectedSocialAccountPolicy;
use App\Policies\PostPolicy;
use App\Policies\PostVariantPolicy;
use Illuminate\Support\Facades\Gate;
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

        // Register policies explicitly (Laravel 12 skeleton has no AuthServiceProvider by default)
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(PostVariant::class, PostVariantPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(ConnectedSocialAccount::class, ConnectedSocialAccountPolicy::class);
    }
}
