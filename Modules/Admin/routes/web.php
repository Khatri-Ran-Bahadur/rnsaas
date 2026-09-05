<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminAuthController;
use Modules\Admin\Http\Controllers\BranchController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\DepartmentController;
use Modules\Admin\Http\Controllers\DesignationController;
use Modules\Admin\Http\Controllers\MemberController;
use Modules\Admin\Http\Controllers\StaffController;
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

        // Branch Management
        Route::controller(BranchController::class)
            ->prefix('branches')
            ->name('branches.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{branch}', 'show')->name('show');
                Route::get('/{branch}/edit', 'edit')->name('edit');
                Route::put('/{branch}', 'update')->name('update');
                Route::patch('/{branch}/activate', 'activate')->name('activate');
                Route::patch('/{branch}/deactivate', 'deactivate')->name('deactivate');
                Route::delete('/{branch}', 'destroy')->name('destroy');
            });

        // Department Management
        Route::controller(DepartmentController::class)
            ->prefix('departments')
            ->name('departments.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{department}', 'update')->name('update');
                Route::patch('/{department}/activate', 'activate')->name('activate');
                Route::patch('/{department}/deactivate', 'deactivate')->name('deactivate');
                Route::delete('/{department}', 'destroy')->name('destroy');
            });

        // Designation Management
        Route::controller(DesignationController::class)
            ->prefix('designations')
            ->name('designations.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{designation}', 'update')->name('update');
                Route::patch('/{designation}/activate', 'activate')->name('activate');
                Route::patch('/{designation}/deactivate', 'deactivate')->name('deactivate');
                Route::delete('/{designation}', 'destroy')->name('destroy');
            });

        // Staff Management
        Route::controller(StaffController::class)
            ->prefix('staff')
            ->name('staff.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{staff}/edit', 'edit')->name('edit');
                Route::put('/{staff}', 'update')->name('update');
                Route::patch('/{staff}/activate', 'activate')->name('activate');
                Route::patch('/{staff}/suspend', 'suspend')->name('suspend');
                Route::delete('/{staff}', 'destroy')->name('destroy');
            });
    });

// Impersonation exit route (accessible to authenticated users in impersonation session)
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('/impersonate/exit', [ImpersonateTenantController::class, 'exit'])
            ->name('impersonate.exit');
    });
