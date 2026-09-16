<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\CouponController;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\SuperAdminBankTransferController;
use Modules\Subscription\Http\Controllers\TenantSubscriptionController;
use Modules\Subscription\Http\Controllers\TenantSubscriptionPortalController;

/*
|--------------------------------------------------------------------------
| SuperAdmin Subscription Management
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin/subscriptions')
    ->name('superadmin.subscriptions.')
    ->group(function () {
        // Plans
        Route::resource('plans', PlanController::class);

        // Bank Transfer Approvals
        Route::controller(SuperAdminBankTransferController::class)->prefix('bank-transfers')->name('bank-transfers.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{transfer}/approve', 'approve')->name('approve');
            Route::post('/{transfer}/reject', 'reject')->name('reject');
        });

        // Coupons
        Route::controller(CouponController::class)->prefix('coupons')->name('coupons.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{coupon}', 'update')->name('update');
            Route::delete('/{coupon}', 'destroy')->name('destroy');
            Route::post('/{coupon}/toggle', 'toggle')->name('toggle');
        });

        // Tenant Subscriptions
        Route::controller(TenantSubscriptionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{subscription}', 'show')->name('show')->whereNumber('subscription');
            Route::post('/{subscription}/cancel', 'cancel')->name('cancel')->whereNumber('subscription');
            Route::post('/{subscription}/reactivate', 'reactivate')->name('reactivate')->whereNumber('subscription');
        });
    });

/*
|--------------------------------------------------------------------------
| Tenant Subscription & Billing Portal
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('admin/subscription')
    ->name('admin.subscription.')
    ->group(function () {
        Route::controller(TenantSubscriptionPortalController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/plans', 'plans')->name('plans');
            Route::post('/bank-transfer', 'submitBankTransfer')->name('bank-transfer');
            Route::post('/add-branches', 'addBranches')->name('add-branches');
            Route::get('/orders', 'orders')->name('orders');
        });
    });

Route::middleware(['auth', 'verified', 'tenant'])->get('admin/subscriptions', function () {
    return redirect()->route('admin.subscription.index');
});
