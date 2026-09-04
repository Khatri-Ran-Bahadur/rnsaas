<?php

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\Http\Controllers\DashboardController;
use Modules\SuperAdmin\Http\Controllers\EmailSettingsController;
use Modules\SuperAdmin\Http\Controllers\PlatformSettingsController;
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

            Route::get('/users/{user}', [UserController::class, 'show'])
                ->middleware('permission:users.view')
                ->name('users.show');

            Route::get('/settings', [PlatformSettingsController::class, 'index'])
                ->middleware('permission:settings.view')
                ->name('settings.index');

            Route::put('/settings', [PlatformSettingsController::class, 'update'])
                ->middleware('permission:settings.update')
                ->name('settings.update');

            Route::post('/settings/cache/clear', [PlatformSettingsController::class, 'clearCache'])
                ->middleware('permission:settings.update')
                ->name('settings.cache.clear');

            Route::post('/settings/email/test', [EmailSettingsController::class, 'sendTestEmail'])
                ->middleware('permission:settings.update')
                ->name('settings.email.test');

            Route::post('/settings/email/test-connection', [EmailSettingsController::class, 'testConnection'])
                ->middleware('permission:settings.update')
                ->name('settings.email.test-connection');
        });
}

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::redirect('/admin', '/superadmin/dashboard');
});
