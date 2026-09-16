<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminAuthController;
use Modules\Admin\Http\Controllers\BranchController;
use Modules\Admin\Http\Controllers\CompanyProfileController;
use Modules\Admin\Http\Controllers\CompanySettingsController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\DepartmentController;
use Modules\Admin\Http\Controllers\DesignationController;
use Modules\Admin\Http\Controllers\InvitationAcceptController;
use Modules\Admin\Http\Controllers\InvitationController;
use Modules\Admin\Http\Controllers\MemberController;
use Modules\Admin\Http\Controllers\OrganizationRoleController;
use Modules\Admin\Http\Controllers\StaffController;
use Modules\Admin\Http\Controllers\TenantSwitcherController;
use Modules\SuperAdmin\Http\Controllers\ImpersonateTenantController;

// Public Organization Admin entry point
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

// Public invitation accept route
Route::get('/invitations/{token}/accept', InvitationAcceptController::class)
    ->name('invitations.accept');

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

        // Invoice friendly aliases
        Route::get('invoices', fn () => redirect('/admin/accounting/invoices'));
        Route::get('invoices/create', fn () => redirect('/admin/accounting/invoices/create'));
        Route::get('sales-invoices', fn () => redirect('/admin/accounting/invoices'));
        Route::get('sales-invoices/create', fn () => redirect('/admin/accounting/invoices/create'));

        Route::post('/tenant/switch/{tenant}', TenantSwitcherController::class)
            ->name('tenant.switch');

        // Member Management
        Route::controller(MemberController::class)
            ->prefix('members')
            ->name('members.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{member}', 'show')->name('show');
                Route::put('/{member}', 'update')->name('update');
                Route::patch('/{member}/suspend', 'suspend')->name('suspend');
                Route::patch('/{member}/reactivate', 'reactivate')->name('reactivate');
                Route::patch('/{member}/activate', 'activate')->name('activate');
                Route::patch('/{member}/password', 'changePassword')->name('password');
                Route::delete('/{member}/revoke', 'revoke')->name('revoke');
            });

        // Role & Permission Management
        Route::controller(OrganizationRoleController::class)
            ->prefix('roles')
            ->name('roles.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{role}/edit', 'edit')->name('edit');
                Route::put('/{role}', 'update')->name('update');
                Route::delete('/{role}', 'destroy')->name('destroy');
            });

        // Invitation Management
        Route::controller(InvitationController::class)
            ->prefix('invitations')
            ->name('invitations.')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::post('/{invitation}/resend', 'resend')->name('resend');
                Route::delete('/{invitation}/revoke', 'revoke')->name('revoke');
            });

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

        // Company Profile Management
        Route::controller(CompanyProfileController::class)
            ->prefix('company-profile')
            ->name('company-profile.')
            ->group(function (): void {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
            });

        // Company Email & Notification Settings
        Route::controller(CompanySettingsController::class)
            ->prefix('company-settings')
            ->name('company-settings.')
            ->group(function (): void {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::post('/email/test', 'sendTestEmail')->name('email.test');
                Route::post('/ai/test', 'testAiConnection')->name('ai.test');
            });

        // Organization User Profile Management
        Route::controller(ProfileController::class)
            ->prefix('profile')
            ->name('profile.')
            ->group(function (): void {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::post('/avatar', 'updateAvatar')->name('avatar.update');
                Route::delete('/avatar', 'deleteAvatar')->name('avatar.delete');
                Route::put('/password', 'updatePassword')->name('password.update');
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
