<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

// SuperAdmin Media routes (supporting both /superadmin/media and legacy /admin/media)
foreach (['superadmin', 'admin'] as $prefix) {
    $namePrefix = $prefix === 'superadmin' ? 'superadmin.' : 'admin.';

    Route::middleware(['auth', 'superadmin'])
        ->prefix($prefix)
        ->name($namePrefix)
        ->group(function (): void {
            Route::get('/media', [MediaController::class, 'page'])->name('media.page');
            Route::get('/media/index', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media/batch', [MediaController::class, 'batchStore'])->name('media.batch');
            Route::post('/media/batch-destroy', [MediaController::class, 'batchDestroy'])->name('media.batch-destroy');
            Route::get('/media/{id}/download', [MediaController::class, 'download'])->name('media.download');
            Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
            Route::post('/media/directories', [MediaController::class, 'createDirectory'])->name('media.directories.create');
            Route::put('/media/directories/{id}', [MediaController::class, 'updateDirectory'])->name('media.directories.update');
            Route::delete('/media/directories/{id}', [MediaController::class, 'destroyDirectory'])->name('media.directories.destroy');
            Route::patch('/media/{id}/directory', [MediaController::class, 'updateMediaDirectory'])->name('media.directory.update');
        });
}

// General / Tenant Authenticated Media Routes (for MediaPicker & multi-tenant use)
Route::middleware(['auth'])->prefix('media')->name('media.')->group(function (): void {
    Route::get('/', [MediaController::class, 'page'])->name('page');
    Route::get('/index', [MediaController::class, 'index'])->name('index');
    Route::post('/batch', [MediaController::class, 'batchStore'])->name('batch');
    Route::post('/batch-destroy', [MediaController::class, 'batch-destroy']);
    Route::get('/{id}/download', [MediaController::class, 'download'])->name('download');
    Route::delete('/{id}', [MediaController::class, 'destroy'])->name('destroy');
    Route::post('/directories', [MediaController::class, 'createDirectory'])->name('directories.create');
    Route::put('/directories/{id}', [MediaController::class, 'updateDirectory'])->name('directories.update');
    Route::delete('/directories/{id}', [MediaController::class, 'destroyDirectory'])->name('directories.destroy');
    Route::patch('/{id}/directory', [MediaController::class, 'updateMediaDirectory'])->name('directory.update');
});
