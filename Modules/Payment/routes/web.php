<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

foreach (['superadmin', 'admin'] as $prefix) {
    $namePrefix = $prefix === 'superadmin' ? 'superadmin.' : 'admin.';

    Route::middleware(['auth', 'superadmin'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])
                ->name('payments.index');
        });
}
