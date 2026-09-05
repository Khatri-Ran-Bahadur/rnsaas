<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\DashboardController;

Route::middleware([
    'auth',
    'verified',
    'tenant',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');
    });