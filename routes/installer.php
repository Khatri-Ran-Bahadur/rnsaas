<?php

use App\Http\Controllers\Installer\InstallController;
use App\Http\Controllers\Installer\UpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Installation Wizard & System Updater Routes
|--------------------------------------------------------------------------
*/

Route::prefix('install')->name('installer.')->group(function (): void {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/license', [InstallController::class, 'license'])->name('license');
    Route::post('/license', [InstallController::class, 'verifyLicense'])->name('license.verify');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/application', [InstallController::class, 'application'])->name('application');
    Route::post('/application', [InstallController::class, 'runMigrationAndSetup'])->name('application.run');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'createAdmin'])->name('admin.create');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

Route::prefix('update')->name('updater.')->group(function (): void {
    Route::get('/', [UpdateController::class, 'index'])->name('index');
    Route::post('/run', [UpdateController::class, 'runUpdate'])->name('run');
});
