<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\AiCopilotController;
use Modules\AI\Http\Controllers\AiSettingsController;
use Modules\AI\Http\Middleware\EnsureTenantHasAiModule;

Route::middleware([
    'auth',
    'tenant',
    'verified',
])
    ->prefix('admin/ai')
    ->name('admin.ai.')
    ->group(function (): void {
        // Publicly readable status for frontend widget
        Route::get('/copilot/status', [AiCopilotController::class, 'status'])->name('copilot.status');

        // Subscription-gated endpoints
        Route::middleware([EnsureTenantHasAiModule::class])->group(function (): void {
            Route::post('/copilot/chat', [AiCopilotController::class, 'chat'])->name('copilot.chat');
            Route::post('/settings/test', [AiSettingsController::class, 'testConnection'])->name('settings.test');
        });
    });
