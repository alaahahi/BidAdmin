<?php

use App\Http\Controllers\Api\App\AppAccountController;
use App\Http\Controllers\Api\App\AppAuthController;
use App\Http\Controllers\Api\App\AppEventController;
use App\Http\Controllers\Api\App\AppLimitController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/me', fn (Request $r) => ['user' => $r->user()->only(['id', 'name', 'email', 'role', 'customer_id']), 'accounts' => $r->user()->customer?->accounts()->get(['accounts.id', 'provider', 'region', 'username'])]);
    Route::post('/activity', [EventController::class, 'activity']);
    Route::post('/bids', [EventController::class, 'bid']);
    Route::post('/watchlist', [EventController::class, 'watchlist']);
});

Route::prefix('app')->group(function () {
    Route::post('/login', [AppAuthController::class, 'login'])->middleware('throttle:app-login');

    Route::middleware(['auth:sanctum', 'ability:app', 'throttle:120,1'])->group(function () {
        Route::get('/accounts', [AppAccountController::class, 'index']);
        Route::get('/accounts/{id}/session', [AppAccountController::class, 'session'])->whereNumber('id');
        Route::post('/events', [AppEventController::class, 'store']);
        Route::get('/limits', [AppLimitController::class, 'show']);
    });
});
