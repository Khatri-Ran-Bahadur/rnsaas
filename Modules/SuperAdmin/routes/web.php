<?php

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\Http\Controllers\DashboardController;
use Modules\SuperAdmin\Http\Controllers\UserController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
    });
