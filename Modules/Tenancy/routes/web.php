<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\TenantController;
use Modules\Tenancy\Http\Controllers\TenantMemberController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin/tenants')
    ->name('tenancy.')
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

// Backward compatibility alias for /tenants -> /admin/tenants
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/tenants', '/admin/tenants');
    Route::redirect('/tenants/create', '/admin/tenants/create');
    Route::get('/tenants/{tenant}', fn ($tenant) => redirect("/admin/tenants/{$tenant}"));
    Route::get('/tenants/{tenant}/edit', fn ($tenant) => redirect("/admin/tenants/{$tenant}/edit"));
});
