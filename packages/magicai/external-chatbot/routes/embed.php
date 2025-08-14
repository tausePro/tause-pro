<?php

use Illuminate\Support\Facades\Route;
use MagicAI\ExternalChatbot\Http\Controllers\Embed\EmbedController;

Route::middleware('web')->group(function () {
    Route::get('embed/{key}.js', [EmbedController::class, 'script'])->name('externalbots.script');
    Route::post('embed/{key}/chat', [EmbedController::class, 'chat'])->name('externalbots.chat');
});


