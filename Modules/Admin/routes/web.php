<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminAuthController;
use Modules\Admin\Http\Controllers\BranchController;
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

        // Branch Management
        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])
            ->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])
            ->name('branches.store');
        Route::get('/branches/{branch}', [BranchController::class, 'show'])
            ->name('branches.show');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
            ->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->name('branches.update');
        Route::patch('/branches/{branch}/activate', [BranchController::class, 'activate'])
            ->name('branches.activate');
        Route::patch('/branches/{branch}/deactivate', [BranchController::class, 'deactivate'])
            ->name('branches.deactivate');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
            ->name('branches.destroy');
    });

// Impersonation exit route (accessible to authenticated users in impersonation session)
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('/impersonate/exit', [ImpersonateTenantController::class, 'exit'])
            ->name('impersonate.exit');
    });
