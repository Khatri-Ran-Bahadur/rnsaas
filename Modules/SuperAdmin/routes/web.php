<?php

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\Http\Controllers\DashboardController;
use Modules\SuperAdmin\Http\Controllers\EmailSettingsController;
use Modules\SuperAdmin\Http\Controllers\ImpersonateTenantController;
use Modules\SuperAdmin\Http\Controllers\PlatformAnalyticsController;
use Modules\SuperAdmin\Http\Controllers\PlatformSettingsController;
use Modules\SuperAdmin\Http\Controllers\RoleController;
use Modules\SuperAdmin\Http\Controllers\SecurityController;
use Modules\SuperAdmin\Http\Controllers\UserController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('superadmin.dashboard'))
            ->name('root');

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

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');

        Route::get('/roles/create', [RoleController::class, 'create'])
            ->middleware('permission:roles.create')
            ->name('roles.create');

        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('roles.store');

        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:roles.update')
            ->name('roles.edit');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.update')
            ->name('roles.update');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('roles.destroy');

        Route::get('/security', [SecurityController::class, 'index'])
            ->middleware('permission:security.view')
            ->name('security.index');

        Route::post('/security/sessions/{session}/revoke', [SecurityController::class, 'revokeSession'])
            ->middleware('permission:security.sessions.revoke')
            ->name('security.sessions.revoke');

        Route::get('/analytics', [PlatformAnalyticsController::class, 'index'])
            ->middleware('permission:analytics.view')
            ->name('analytics.index');

        // Organization Impersonation ("Login as Admin")
        Route::post('/tenants/{tenant}/impersonate', [ImpersonateTenantController::class, 'impersonate'])
            ->name('tenants.impersonate');
    });
