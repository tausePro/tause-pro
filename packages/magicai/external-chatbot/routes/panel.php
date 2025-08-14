<?php

use Illuminate\Support\Facades\Route;
use MagicAI\ExternalChatbot\Http\Controllers\User\ExternalBotController;

Route::middleware(['web','auth'])->prefix('dashboard')->group(function () {
    Route::get('external-bots', [ExternalBotController::class, 'index'])->name('externalbots.index');
    Route::get('external-bots/create', [ExternalBotController::class, 'create'])->name('externalbots.create');
    Route::post('external-bots', [ExternalBotController::class, 'store'])->name('externalbots.store');
    Route::get('external-bots/{bot}/embed', [ExternalBotController::class, 'embed'])->name('externalbots.embed');
});


