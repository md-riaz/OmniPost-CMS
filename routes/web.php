<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\Dashboard\PublishNowController;
use App\Http\Controllers\Dashboard\PostWorkflowController;
use App\Models\Brand;

Route::get('/', function () {
    return view('welcome');
});

// OAuth Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/{platform}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/{platform}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
    Route::post('/oauth/accounts/{account}/disconnect', [OAuthController::class, 'disconnect'])->name('oauth.disconnect');
    Route::get('/oauth/accounts/{account}/reconnect', [OAuthController::class, 'reconnect'])->name('oauth.reconnect');
    
    // Dashboard Actions
    Route::post('/dashboard/post-variants/{variant}/publish-now', PublishNowController::class)->name('dashboard.post-variants.publish-now');
    
    // Post Workflow Actions
    Route::post('/dashboard/posts/{post}/submit-for-approval', [PostWorkflowController::class, 'submitForApproval'])->name('dashboard.posts.submit-for-approval');
    Route::post('/dashboard/posts/{post}/approve', [PostWorkflowController::class, 'approve'])->name('dashboard.posts.approve');
    Route::post('/dashboard/posts/{post}/reject', [PostWorkflowController::class, 'reject'])->name('dashboard.posts.reject');
    Route::post('/dashboard/posts/{post}/comments', [PostWorkflowController::class, 'addComment'])->name('dashboard.posts.add-comment');
    Route::get('/dashboard/posts/{post}/comments', [PostWorkflowController::class, 'showComments'])->name('dashboard.posts.comments');
    
    // Calendar View
    Route::get('/dashboard/calendar', function () {
        $brands = Brand::all();
        return view('dashboard.calendar', compact('brands'));
    })->name('dashboard.calendar');
    
    // Analytics Routes
    Route::get('/dashboard/analytics', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'index'])
        ->name('dashboard.analytics.index');
    Route::get('/dashboard/analytics/posts/{post}', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'postPerformance'])
        ->name('dashboard.analytics.post-performance');
    Route::get('/dashboard/analytics/export', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'export'])
        ->name('dashboard.analytics.export');
});
