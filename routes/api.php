<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/me', fn (Request $r) => ['user' => $r->user()->only(['id', 'name', 'email', 'role', 'customer_id']), 'accounts' => $r->user()->customer?->accounts()->get(['accounts.id', 'provider', 'region', 'username'])]);
    Route::post('/activity', [EventController::class, 'activity']);
    Route::post('/bids', [EventController::class, 'bid']);
    Route::post('/watchlist', [EventController::class, 'watchlist']);
});
