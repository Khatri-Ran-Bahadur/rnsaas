<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['auth:sanctum'])->prefix('v1/media')->name('api.media.')->group(function (): void {
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::post('/batch', [MediaController::class, 'batchStore'])->name('batch');
    Route::get('/{id}/download', [MediaController::class, 'download'])->name('download');
    Route::delete('/{id}', [MediaController::class, 'destroy'])->name('destroy');
    Route::post('/directories', [MediaController::class, 'createDirectory'])->name('directories.create');
    Route::put('/directories/{id}', [MediaController::class, 'updateDirectory'])->name('directories.update');
    Route::delete('/directories/{id}', [MediaController::class, 'destroyDirectory'])->name('directories.destroy');
});
