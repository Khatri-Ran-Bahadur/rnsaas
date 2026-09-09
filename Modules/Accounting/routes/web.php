<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Reports\AccountingReportController;
use Modules\Accounting\Http\Controllers\Statements\FinancialStatementController;

Route::middleware(['auth', 'tenant'])
    ->prefix('admin/accounting')
    ->name('admin.accounting.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::prefix('reports')
            ->name('reports.')
            ->controller(AccountingReportController::class)
            ->group(function () {
                Route::get(
                    'general-ledger',
                    'generalLedger'
                )->name('general-ledger');

                Route::get(
                    'trial-balance',
                    'trialBalance'
                )->name('trial-balance');

                Route::get(
                    'accounts/{accountId}/balance',
                    'accountBalance'
                )->name('account-balance');
            });

        /*
        |--------------------------------------------------------------------------
        | Financial Statements
        |--------------------------------------------------------------------------
        */

        Route::prefix('statements')
            ->name('statements.')
            ->controller(FinancialStatementController::class)
            ->group(function () {
                Route::get(
                    'profit-and-loss',
                    'profitAndLoss'
                )->name('profit-and-loss');

                Route::get(
                    'balance-sheet',
                    'balanceSheet'
                )->name('balance-sheet');
            });
    });
