<?php

use Illuminate\Support\Facades\Route;
use Modules\Tax\Http\Controllers\TaxAccountMappingController;
use Modules\Tax\Http\Controllers\TaxAuditController;
use Modules\Tax\Http\Controllers\TaxCategoryController;
use Modules\Tax\Http\Controllers\TaxDashboardController;
use Modules\Tax\Http\Controllers\TaxExemptionController;
use Modules\Tax\Http\Controllers\TaxRateController;
use Modules\Tax\Http\Controllers\TaxReportController;
use Modules\Tax\Http\Controllers\TaxRuleController;
use Modules\Tax\Http\Controllers\TaxSettingController;
use Modules\Tax\Http\Controllers\TaxTypeController;

Route::middleware(['auth', 'tenant'])->prefix('admin/tax')->name('admin.tax.')->group(function () {
    // Tax Dashboard
    Route::get('/', [TaxDashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [TaxDashboardController::class, 'index'])->name('dashboard');

    // Tax Types
    Route::get('/types', [TaxTypeController::class, 'index'])->name('types.index');
    Route::post('/types', [TaxTypeController::class, 'store'])->name('types.store');
    Route::put('/types/{id}', [TaxTypeController::class, 'update'])->name('types.update');
    Route::delete('/types/{id}', [TaxTypeController::class, 'destroy'])->name('types.destroy');

    // Tax Rates
    Route::get('/rates', [TaxRateController::class, 'index'])->name('rates.index');
    Route::post('/rates', [TaxRateController::class, 'store'])->name('rates.store');
    Route::put('/rates/{id}', [TaxRateController::class, 'update'])->name('rates.update');
    Route::delete('/rates/{id}', [TaxRateController::class, 'destroy'])->name('rates.destroy');

    // Tax Categories
    Route::get('/categories', [TaxCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [TaxCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [TaxCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [TaxCategoryController::class, 'destroy'])->name('categories.destroy');

    // Tax Rules
    Route::get('/rules', [TaxRuleController::class, 'index'])->name('rules.index');
    Route::post('/rules', [TaxRuleController::class, 'store'])->name('rules.store');
    Route::put('/rules/{id}', [TaxRuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{id}', [TaxRuleController::class, 'destroy'])->name('rules.destroy');

    // Tax Exemptions
    Route::get('/exemptions', [TaxExemptionController::class, 'index'])->name('exemptions.index');
    Route::post('/exemptions', [TaxExemptionController::class, 'store'])->name('exemptions.store');
    Route::put('/exemptions/{id}', [TaxExemptionController::class, 'update'])->name('exemptions.update');
    Route::delete('/exemptions/{id}', [TaxExemptionController::class, 'destroy'])->name('exemptions.destroy');

    // Tax Settings & Profile
    Route::get('/settings', [TaxSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [TaxSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/apply-preset', [TaxSettingController::class, 'applyPreset'])->name('settings.apply-preset');

    // Tax GL Account Mappings
    Route::get('/accounts', [TaxAccountMappingController::class, 'index'])->name('accounts.index');
    Route::put('/accounts', [TaxAccountMappingController::class, 'update'])->name('accounts.update');

    // Tax Reports
    Route::get('/reports', [TaxReportController::class, 'index'])->name('reports.index');

    // Tax Audit Trail
    Route::get('/audit', [TaxAuditController::class, 'index'])->name('audit.index');
});
