<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Dashboard\PublishNowController;
use App\Http\Controllers\Dashboard\PostWorkflowController;
use App\Http\Controllers\Dashboard\CrisisModeController;
use App\Http\Controllers\Dashboard\ConnectedAccountsController;
use App\Http\Controllers\Dashboard\QueueController;
use App\Models\Brand;

Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

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
    
    // Crisis Mode Routes
    Route::get('/dashboard/brands/{brand}/crisis-mode', [CrisisModeController::class, 'index'])->name('dashboard.crisis-mode.index');
    Route::post('/dashboard/brands/{brand}/crisis-mode/enable', [CrisisModeController::class, 'enable'])->name('dashboard.crisis-mode.enable');
    Route::post('/dashboard/brands/{brand}/crisis-mode/disable', [CrisisModeController::class, 'disable'])->name('dashboard.crisis-mode.disable');
    
    // Calendar View
    Route::get('/dashboard/calendar', function () {
        $brands = Brand::all();
        return view('dashboard.calendar', compact('brands'));
    })->name('dashboard.calendar');
    
    // Connect Accounts
    Route::get('/dashboard/connect-accounts', [ConnectedAccountsController::class, 'index'])
        ->name('dashboard.connect-accounts');
    
    // Analytics Routes
    Route::get('/dashboard/analytics', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'index'])
        ->name('dashboard.analytics.index');
    Route::get('/dashboard/analytics/posts/{post}', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'postPerformance'])
        ->name('dashboard.analytics.post-performance');
    Route::get('/dashboard/analytics/export', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'export'])
        ->name('dashboard.analytics.export');

    // Role queues
    Route::get('/dashboard/queues/editor', [QueueController::class, 'editor'])->name('dashboard.queues.editor');
    Route::get('/dashboard/queues/approver', [QueueController::class, 'approver'])->name('dashboard.queues.approver');
    Route::get('/dashboard/queues/publisher', [QueueController::class, 'publisher'])->name('dashboard.queues.publisher');
    Route::get('/dashboard/queues/manager', [QueueController::class, 'manager'])->name('dashboard.queues.manager');
});
