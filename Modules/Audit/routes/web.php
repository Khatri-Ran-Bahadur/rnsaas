<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditLogController;

foreach (['superadmin', 'admin'] as $prefix) {
    $namePrefix = $prefix === 'superadmin' ? 'superadmin.' : 'admin.';

    Route::middleware(['auth', 'superadmin'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->name('audit-logs.index');
        });
}
