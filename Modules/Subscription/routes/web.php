<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\TenantSubscriptionController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin/subscriptions')
    ->name('superadmin.subscriptions.')
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
