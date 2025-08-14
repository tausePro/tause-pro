<?php

use Illuminate\Support\Facades\Route;
use MagicAI\BrainBrand\Http\Controllers\BrainBrandController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('brain-brand', [BrainBrandController::class, 'index'])->name('brainbrand.index');
        Route::get('brain-brand/metrics', [BrainBrandController::class, 'metrics'])->name('brainbrand.metrics');
        Route::get('brain-brand/knowledge', [BrainBrandController::class, 'knowledge'])->name('brainbrand.knowledge');
        Route::post('brain-brand/research', [BrainBrandController::class, 'research'])->name('brainbrand.research');
        Route::get('brain-brand/research', [BrainBrandController::class, 'research']);
        Route::post('brain-brand/train', [BrainBrandController::class, 'train'])->name('brainbrand.train');
        Route::get('brain-brand/train', [BrainBrandController::class, 'train']);
    });
});


