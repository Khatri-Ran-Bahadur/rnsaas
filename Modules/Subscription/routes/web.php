<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\TenantSubscriptionController;

foreach (['superadmin/subscriptions', 'admin/subscriptions'] as $prefix) {
    $namePrefix = str_starts_with($prefix, 'superadmin') ? 'superadmin.subscriptions.' : 'admin.subscriptions.';

    Route::middleware(['auth', 'superadmin'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Subscription Plans
            |--------------------------------------------------------------------------
            */
            Route::resource('plans', PlanController::class);

            /*
            |--------------------------------------------------------------------------
            | Tenant Subscriptions
            |--------------------------------------------------------------------------
            */
            Route::controller(TenantSubscriptionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{subscription}', 'show')->name('show');
                Route::post('/{subscription}/cancel', 'cancel')->name('cancel');
                Route::post('/{subscription}/reactivate', 'reactivate')->name('reactivate');
            });
        });
}
