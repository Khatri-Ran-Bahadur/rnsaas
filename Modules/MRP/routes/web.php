<?php

use Illuminate\Support\Facades\Route;
use Modules\MRP\Http\Controllers\MrpBomController;
use Modules\MRP\Http\Controllers\MrpCalendarController;
use Modules\MRP\Http\Controllers\MrpCostController;
use Modules\MRP\Http\Controllers\MrpDashboardController;
use Modules\MRP\Http\Controllers\MrpFinishedGoodController;
use Modules\MRP\Http\Controllers\MrpMaterialIssueController;
use Modules\MRP\Http\Controllers\MrpPlanningController;
use Modules\MRP\Http\Controllers\MrpProductionRunController;
use Modules\MRP\Http\Controllers\MrpQualityController;
use Modules\MRP\Http\Controllers\MrpReportController;
use Modules\MRP\Http\Controllers\MrpSettingController;
use Modules\MRP\Http\Controllers\MrpTraceabilityController;
use Modules\MRP\Http\Controllers\MrpWorkCenterController;
use Modules\MRP\Http\Controllers\MrpWorkOrderController;

Route::middleware(['auth', 'tenant'])->prefix('admin/mrp')->name('admin.mrp.')->group(function () {
    // Dashboard
    Route::get('/', [MrpDashboardController::class, 'index'])->name('dashboard');

    // Bill of Materials (BOM)
    Route::prefix('bom')->name('bom.')->group(function () {
        Route::get('/', [MrpBomController::class, 'index'])->name('index');
        Route::get('/create', [MrpBomController::class, 'create'])->name('create');
        Route::post('/', [MrpBomController::class, 'store'])->name('store');
        Route::get('/{id}', [MrpBomController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [MrpBomController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MrpBomController::class, 'update'])->name('update');
        Route::post('/{id}/approve', [MrpBomController::class, 'approve'])->name('approve');
        Route::post('/{id}/duplicate', [MrpBomController::class, 'duplicate'])->name('duplicate');
    });

    // Material Planning (MRP Engine)
    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/', [MrpPlanningController::class, 'index'])->name('index');
        Route::post('/calculate', [MrpPlanningController::class, 'runCalculation'])->name('calculate');
    });

    // Work Orders & Manufacturing Orders
    Route::prefix('work-orders')->name('work-orders.')->group(function () {
        Route::get('/', [MrpWorkOrderController::class, 'index'])->name('index');
        Route::get('/create', [MrpWorkOrderController::class, 'create'])->name('create');
        Route::post('/', [MrpWorkOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [MrpWorkOrderController::class, 'show'])->name('show');
        Route::post('/{id}/status', [MrpWorkOrderController::class, 'updateStatus'])->name('status');
    });
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [MrpWorkOrderController::class, 'index'])->name('index');
        Route::get('/create', [MrpWorkOrderController::class, 'create'])->name('create');
        Route::post('/', [MrpWorkOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [MrpWorkOrderController::class, 'show'])->name('show');
    });

    // Shop Floor Execution & Touch Screen
    Route::prefix('execution')->name('execution.')->group(function () {
        Route::get('/', [MrpProductionRunController::class, 'execution'])->name('index');
        Route::post('/action', [MrpProductionRunController::class, 'recordAction'])->name('action');
        Route::post('/output', [MrpProductionRunController::class, 'reportOutput'])->name('output');
        Route::post('/scrap', [MrpProductionRunController::class, 'reportScrap'])->name('scrap');
    });

    // Material Issues & Returns
    Route::prefix('material-issues')->name('material-issues.')->group(function () {
        Route::get('/', [MrpMaterialIssueController::class, 'index'])->name('index');
        Route::post('/', [MrpMaterialIssueController::class, 'store'])->name('store');
        Route::post('/return', [MrpMaterialIssueController::class, 'returnMaterial'])->name('return');
    });

    // Finished Goods Receipts & By-products
    Route::prefix('finished-goods')->name('finished-goods.')->group(function () {
        Route::get('/', [MrpFinishedGoodController::class, 'index'])->name('index');
        Route::post('/', [MrpFinishedGoodController::class, 'store'])->name('store');
    });

    // Quality Control & Inspection
    Route::prefix('quality')->name('quality.')->group(function () {
        Route::get('/', [MrpQualityController::class, 'index'])->name('index');
        Route::post('/', [MrpQualityController::class, 'store'])->name('store');
    });

    // Work Centers & Routing & Machines
    Route::prefix('work-centers')->name('work-centers.')->group(function () {
        Route::get('/', [MrpWorkCenterController::class, 'index'])->name('index');
        Route::post('/', [MrpWorkCenterController::class, 'store'])->name('store');
    });

    // Production Calendar & Scheduling
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [MrpCalendarController::class, 'index'])->name('index');
    });

    // Batch & Lot Traceability Genealogy
    Route::prefix('traceability')->name('traceability.')->group(function () {
        Route::get('/', [MrpTraceabilityController::class, 'index'])->name('index');
    });

    // Costing & Variance Analysis
    Route::prefix('costs')->name('costs.')->group(function () {
        Route::get('/', [MrpCostController::class, 'index'])->name('index');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [MrpReportController::class, 'index'])->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [MrpSettingController::class, 'index'])->name('index');
        Route::post('/', [MrpSettingController::class, 'update'])->name('update');
    });
});
