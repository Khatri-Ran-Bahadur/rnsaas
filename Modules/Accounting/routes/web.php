<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Attachments\AccountingAttachmentController;
use Modules\Accounting\Http\Controllers\Customers\CustomerController;
use Modules\Accounting\Http\Controllers\Invoices\SalesInvoiceController;
use Modules\Accounting\Http\Controllers\Payments\CustomerPaymentController;
use Modules\Accounting\Http\Controllers\PurchaseBills\PurchaseBillController;
use Modules\Accounting\Http\Controllers\Reports\AccountingReportController;
use Modules\Accounting\Http\Controllers\Statements\FinancialStatementController;
use Modules\Accounting\Http\Controllers\VendorPayments\VendorPaymentController;
use Modules\Accounting\Http\Controllers\Vendors\VendorController;
use Modules\Accounting\Http\Middleware\EnsureAccountingModuleEnabled;

Route::middleware(['auth', 'tenant', EnsureAccountingModuleEnabled::class])
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
                Route::get('/', 'index')->name('index');

                Route::get(
                    'general-ledger',
                    'generalLedger'
                )->name('general-ledger');

                Route::get(
                    'general-ledger/print',
                    'generalLedgerPrint'
                )->name('general-ledger.print');

                Route::get(
                    'trial-balance',
                    'trialBalance'
                )->name('trial-balance');

                Route::get(
                    'trial-balance/print',
                    'trialBalancePrint'
                )->name('trial-balance.print');

                Route::get(
                    'accounts/{accountId}/balance',
                    'accountBalance'
                )->name('account-balance');

                Route::get(
                    'payable-aging',
                    'payableAging'
                )->name('payable-aging');

                Route::get(
                    'receivable-aging',
                    'receivableAging'
                )->name('receivable-aging');

                Route::get(
                    'customer-statement',
                    'customerStatement'
                )->name('customer-statement.index');

                Route::get(
                    'customers/{customer}/statement',
                    'customerStatement'
                )->name('customer-statement');

                Route::get(
                    'customers/{customer}/balance',
                    'customerBalance'
                )->name('customer-balance');

                Route::get(
                    'vendors/{vendor}/balance',
                    'vendorBalance'
                )->name('vendor-balance');

                Route::get(
                    'vendors/{vendor}/statement',
                    'vendorStatement'
                )->name('vendor-statement');
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
                    'profit-and-loss/print',
                    'profitAndLossPrint'
                )->name('profit-and-loss.print');

                Route::get(
                    'balance-sheet',
                    'balanceSheet'
                )->name('balance-sheet');

                Route::get(
                    'balance-sheet/print',
                    'balanceSheetPrint'
                )->name('balance-sheet.print');
            });

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        Route::prefix('customers')
            ->name('customers.')
            ->controller(CustomerController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{customer}', 'show')->name('show');
                Route::get('{customer}/edit', 'edit')->name('edit');
                Route::put('{customer}', 'update')->name('update');
                Route::match(['patch', 'post'], '{customer}/status', 'toggleStatus')->name('toggle-status');
                Route::match(['patch', 'post'], '{customer}/toggle-status', 'toggleStatus')->name('toggle-status.alias');
            });

        Route::prefix('invoices')
            ->name('invoices.')
            ->controller(SalesInvoiceController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{invoice}', 'show')->name('show');
                Route::get('{invoice}/edit', 'edit')->name('edit');
                Route::put('{invoice}', 'update')->name('update');
                Route::post('{invoice}/issue', 'issue')->name('issue');
                Route::post('{invoice}/post', 'post')->name('post');
                Route::post('{invoice}/void', 'void')->name('void');
            });

        Route::prefix('payments')
            ->name('payments.')
            ->controller(CustomerPaymentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{payment}', 'show')->name('show');
                Route::post('{payment}/allocate', 'allocate')->name('allocate');
                Route::post('{payment}/post', 'post')->name('post');
            });

        /*
        |--------------------------------------------------------------------------
        | Vendors
        |--------------------------------------------------------------------------
        */

        Route::prefix('vendors')
            ->name('vendors.')
            ->controller(VendorController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{vendor}', 'show')->name('show');
                Route::get('{vendor}/edit', 'edit')->name('edit');
                Route::put('{vendor}', 'update')->name('update');
                Route::match(['patch', 'post'], '{vendor}/status', 'toggleStatus')->name('toggle-status');
                Route::match(['patch', 'post'], '{vendor}/toggle-status', 'toggleStatus')->name('toggle-status.alias');
            });

        /*
        |--------------------------------------------------------------------------
        | Purchase Bills
        |--------------------------------------------------------------------------
        */

        Route::prefix('purchase-bills')
            ->name('purchase-bills.')
            ->controller(PurchaseBillController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{bill}', 'show')->name('show');
                Route::get('{bill}/edit', 'edit')->name('edit');
                Route::put('{bill}', 'update')->name('update');
                Route::post('{bill}/issue', 'issue')->name('issue');
                Route::post('{bill}/post', 'post')->name('post');
                Route::post('{bill}/void', 'void')->name('void');
            });

        /*
        |--------------------------------------------------------------------------
        | Vendor Payments
        |--------------------------------------------------------------------------
        */

        Route::prefix('vendor-payments')
            ->name('vendor-payments.')
            ->controller(VendorPaymentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{payment}', 'show')->name('show');
                Route::post('{payment}/allocate', 'allocate')->name('allocate');
                Route::post('{payment}/post', 'post')->name('post');
            });

        Route::post(
            'invoices/{invoice}/attachments',
            [AccountingAttachmentController::class, 'storeInvoiceAttachment']
        )->name('invoices.attachments.store');

        Route::post(
            'payments/{payment}/attachments',
            [AccountingAttachmentController::class, 'storePaymentAttachment']
        )->name('payments.attachments.store');

        Route::get(
            'attachments/{attachment}/download',
            [AccountingAttachmentController::class, 'download']
        )->name('attachments.download');

        Route::delete(
            'attachments/{attachment}',
            [AccountingAttachmentController::class, 'destroy']
        )->name('attachments.destroy');
    });
