<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\PosController;
use Modules\POS\Http\Controllers\PosKitchenController;
use Modules\POS\Http\Controllers\PosOrderController;
use Modules\POS\Http\Controllers\PosReportController;
use Modules\POS\Http\Controllers\PosSettingController;
use Modules\POS\Http\Controllers\PosShiftController;
use Modules\POS\Http\Controllers\PosTableController;

Route::middleware(['auth', 'tenant'])->prefix('admin/pos')->name('admin.pos.')->group(function () {
    // Primary POS Register
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::get('/register', [PosController::class, 'index'])->name('register');
    Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    Route::post('/hold', [PosController::class, 'hold'])->name('hold');
    Route::post('/resume', [PosController::class, 'resume'])->name('resume');
    Route::post('/void', [PosController::class, 'void'])->name('void');

    // Restaurant Table Floor Plan
    Route::get('/floor-plan', [PosTableController::class, 'index'])->name('floor-plan');
    Route::post('/tables/update-status', [PosTableController::class, 'updateStatus'])->name('tables.status');
    Route::post('/tables/move', [PosTableController::class, 'move'])->name('tables.move');
    Route::post('/tables/merge', [PosTableController::class, 'merge'])->name('tables.merge');

    // Kitchen Display System (KDS)
    Route::get('/kds', [PosKitchenController::class, 'index'])->name('kds');
    Route::post('/kds/update-status', [PosKitchenController::class, 'updateStatus'])->name('kds.status');

    // Cashier Shifts & Cash Drawer
    Route::get('/shifts', [PosShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/open', [PosShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/movement', [PosShiftController::class, 'recordMovement'])->name('shifts.movement');
    Route::post('/shifts/close', [PosShiftController::class, 'close'])->name('shifts.close');

    // Orders & Sales History
    Route::get('/orders', [PosOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [PosOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/refund', [PosOrderController::class, 'refund'])->name('orders.refund');

    // POS Analytics & Reports
    Route::get('/reports', [PosReportController::class, 'index'])->name('reports.index');

    // Receipt Templates & Hardware Print Setup
    Route::get('/receipt-templates', [PosSettingController::class, 'receiptTemplates'])->name('receipt-templates');
    Route::post('/receipt-templates', [PosSettingController::class, 'saveReceiptTemplate'])->name('receipt-templates.save');
    Route::get('/print-settings', [PosSettingController::class, 'printSettings'])->name('print-settings');
    Route::post('/print-settings', [PosSettingController::class, 'savePrintSettings'])->name('print-settings.save');
});
