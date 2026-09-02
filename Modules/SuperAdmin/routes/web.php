<?php

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\Http\Controllers\DashboardController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');
    });
