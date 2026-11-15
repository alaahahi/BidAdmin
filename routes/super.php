<?php

use App\Http\Controllers\Super\AuthController;
use App\Http\Controllers\Super\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('super')->name('super.')->group(function () {
    Route::middleware('guest:super')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:super')->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::post('enter', [AuthController::class, 'enter'])->name('enter');
        Route::get('/', [TenantController::class, 'index'])->name('dashboard');
        Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('tenants/{managedTenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('tenants/{managedTenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('tenants/{managedTenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{managedTenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::delete('tenants/{managedTenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
    });
});
