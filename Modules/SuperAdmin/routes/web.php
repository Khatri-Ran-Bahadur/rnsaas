<?php

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\Http\Controllers\DashboardController;
use Modules\SuperAdmin\Http\Controllers\UserController;

foreach (['superadmin', 'admin'] as $prefix) {
    $namePrefix = $prefix === 'superadmin' ? 'superadmin.' : 'admin.';

    Route::middleware(['auth', 'superadmin'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function () use ($prefix) {
            if ($prefix === 'superadmin') {
                Route::get('/', fn () => redirect()->route('superadmin.dashboard'))
                    ->name('root');
            }

            Route::get('/dashboard', DashboardController::class)
                ->name('dashboard');

            Route::get('/users', [UserController::class, 'index'])
                ->middleware('permission:users.view')
                ->name('users.index');
        });
}

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::redirect('/admin', '/superadmin/dashboard');
});
