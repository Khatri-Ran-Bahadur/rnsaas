<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\PlanController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('admin/subscriptions')
    ->name('admin.subscriptions.')
    ->group(function () {
        Route::resource('plans', PlanController::class);
    });
