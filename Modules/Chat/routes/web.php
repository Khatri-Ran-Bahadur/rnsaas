<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;

Route::middleware(['auth', 'tenant'])->prefix('admin/chat')->name('admin.chat.')->group(function () {
    // Main Chat Interface
    Route::get('/', [ChatController::class, 'index'])->name('index');

    // Conversation Management
    Route::post('/direct', [ChatController::class, 'startDirect'])->name('direct');
    Route::post('/group', [ChatController::class, 'startGroup'])->name('group');

    // Conversation Messages
    Route::get('/conversations/{id}/messages', [ChatController::class, 'getMessages'])->name('messages.index');
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage'])->name('messages.store');
    Route::put('/conversations/{conversationId}/messages/{messageId}', [ChatController::class, 'editMessage'])->name('messages.update');
    Route::delete('/conversations/{conversationId}/messages/{messageId}', [ChatController::class, 'deleteMessage'])->name('messages.destroy');
    Route::post('/conversations/{conversationId}/messages/{messageId}/react', [ChatController::class, 'reactMessage'])->name('messages.react');
    Route::post('/conversations/{id}/read', [ChatController::class, 'markAsRead'])->name('messages.read');

    // Public Channel Broadcast Demo (Announcement)
    Route::post('/announcement', [ChatController::class, 'broadcastAnnouncement'])->name('announcement');
});
