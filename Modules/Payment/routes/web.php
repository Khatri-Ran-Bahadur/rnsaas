<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])
            ->name('payments.index');
    });