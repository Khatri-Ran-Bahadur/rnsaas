<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\CategoryController;
use Modules\Inventory\Http\Controllers\DashboardController;
use Modules\Inventory\Http\Controllers\ItemController;
use Modules\Inventory\Http\Controllers\LocationController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\UnitController;

Route::middleware(['auth', 'tenant'])
    ->prefix('admin/inventory')
    ->name('admin.inventory.')
    ->group(function () {
        // Dashboard / Overview
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('overview');

        // Items / Products
        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/', [ItemController::class, 'index'])->name('index');
            Route::get('/create', [ItemController::class, 'create'])->name('create');
            Route::post('/', [ItemController::class, 'store'])->name('store');
            Route::get('/{item}', [ItemController::class, 'show'])->name('show');
            Route::get('/{item}/edit', [ItemController::class, 'edit'])->name('edit');
            Route::put('/{item}', [ItemController::class, 'update'])->name('update');
            Route::delete('/{item}', [ItemController::class, 'destroy'])->name('destroy');
        });

        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Units
        Route::prefix('units')->name('units.')->group(function () {
            Route::get('/', [UnitController::class, 'index'])->name('index');
            Route::post('/', [UnitController::class, 'store'])->name('store');
            Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
        });

        // Locations / Warehouses
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/', [LocationController::class, 'index'])->name('index');
            Route::post('/', [LocationController::class, 'store'])->name('store');
            Route::put('/{location}', [LocationController::class, 'update'])->name('update');
            Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');
        });

        // Stock Operations (Ledger, Transfer, Adjustment)
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('/ledger', [StockController::class, 'ledger'])->name('ledger');
            Route::get('/transfers', [StockController::class, 'transfer'])->name('transfers');
            Route::get('/adjustments', [StockController::class, 'adjustment'])->name('adjustments');
            Route::post('/adjustments', [StockController::class, 'storeAdjustment'])->name('adjustments.store');
        });
    });
