<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\TenantController;
use Modules\Tenancy\Http\Controllers\TenantMemberController;

Route::middleware(['auth', 'verified'])
    ->prefix('tenants')
    ->name('tenancy.')
    ->group(function () {
        Route::controller(TenantController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{tenant}', 'show')->name('show');
            Route::get('/{tenant}/edit', 'edit')->name('edit');
        });

        Route::controller(TenantMemberController::class)->prefix('{tenant}/members')->name('members.')->group(function () {
            Route::post('/invite', 'invite')->name('invite');
            Route::post('/{user}/suspend', 'suspend')->name('suspend');
            Route::post('/{user}/revoke', 'revoke')->name('revoke');
            Route::post('/{user}/reactivate', 'reactivate')->name('reactivate');
        });
    });
