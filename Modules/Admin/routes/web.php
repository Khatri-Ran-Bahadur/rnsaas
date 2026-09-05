<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminAuthController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\MemberController;
use Modules\Admin\Http\Controllers\TenantSwitcherController;
use Modules\SuperAdmin\Http\Controllers\ImpersonateTenantController;

// Public Organization Admin entry point
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

// Authenticated Organization Admin routes
Route::middleware([
    'auth',
    'verified',
    'tenant',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))
            ->name('root');

        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');

        Route::post('/tenant/switch/{tenant}', TenantSwitcherController::class)
            ->name('tenant.switch');

        Route::get('/members', [MemberController::class, 'index'])
            ->name('members.index');
    });

// Impersonation exit route (accessible to authenticated users in impersonation session)
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('/impersonate/exit', [ImpersonateTenantController::class, 'exit'])
            ->name('impersonate.exit');
    });
