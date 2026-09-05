<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditLogController;

Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
    });
