<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\Dashboard\PublishNowController;

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
});
