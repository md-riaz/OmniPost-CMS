<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CalendarController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Calendar API
Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index']);
    Route::post('/calendar/{variant}/reschedule', [CalendarController::class, 'updateSchedule']);
});
