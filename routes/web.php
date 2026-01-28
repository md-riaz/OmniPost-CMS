<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuth\OAuthController;

Route::get('/', function () {
    return view('welcome');
});

// OAuth Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/{platform}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/{platform}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
    Route::post('/oauth/accounts/{account}/disconnect', [OAuthController::class, 'disconnect'])->name('oauth.disconnect');
    Route::get('/oauth/accounts/{account}/reconnect', [OAuthController::class, 'reconnect'])->name('oauth.reconnect');
});
