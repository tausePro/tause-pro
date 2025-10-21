<?php

use App\Extensions\Chatbot\System\Http\Controllers\SalesAgentConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('api/v1/chatbots/{chatbot}')->name('api.v1.chatbots.')->group(function () {
        Route::get('sales-agent-config', [SalesAgentConfigController::class, 'show'])->name('sales-agent-config.show');
        Route::post('sales-agent-config', [SalesAgentConfigController::class, 'update'])->name('sales-agent-config.update');
        Route::post('sales-agent-config/preview', [SalesAgentConfigController::class, 'preview'])->name('sales-agent-config.preview');
    });
});
