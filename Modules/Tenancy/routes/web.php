<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\TenantController;
use Modules\Tenancy\Http\Controllers\TenantMemberController;

foreach (['superadmin/tenants', 'admin/tenants'] as $prefix) {
    $namePrefix = str_starts_with($prefix, 'superadmin') ? 'superadmin.tenancy.' : 'tenancy.';

    Route::middleware(['auth', 'verified'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function () {
            Route::controller(TenantController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{tenant}', 'show')->name('show');
                Route::get('/{tenant}/edit', 'edit')->name('edit');
                Route::put('/{tenant}', 'update')->name('update');
            });

            Route::controller(TenantMemberController::class)->prefix('{tenant}/members')->name('members.')->group(function () {
                Route::post('/invite', 'invite')->name('invite');
                Route::post('/{user}/suspend', 'suspend')->name('suspend');
                Route::post('/{user}/revoke', 'revoke')->name('revoke');
                Route::post('/{user}/reactivate', 'reactivate')->name('reactivate');
            });
        });
}

// Backward compatibility alias for /tenants -> /superadmin/tenants
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/tenants', '/superadmin/tenants');
    Route::redirect('/tenants/create', '/superadmin/tenants/create');
    Route::get('/tenants/{tenant}', fn ($tenant) => redirect("/superadmin/tenants/{$tenant}"));
    Route::get('/tenants/{tenant}/edit', fn ($tenant) => redirect("/superadmin/tenants/{$tenant}/edit"));
});
