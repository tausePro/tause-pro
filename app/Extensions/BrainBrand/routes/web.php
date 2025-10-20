<?php

use App\Extensions\BrainBrand\System\Http\Controllers\BrainBrandTrainController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'dashboard/user/brain-brand',
    'middleware' => ['web', 'auth'],
    'as' => 'dashboard.user.brain-brand.',
], function () {
    Route::get('', [BrainBrandTrainController::class, 'index'])->name('index');
    Route::get('{brainBrand}', [BrainBrandTrainController::class, 'show'])->name('train');
    Route::get('{brainBrand}/data', [BrainBrandTrainController::class, 'trainData'])->name('data');
    Route::post('{brainBrand}/url', [BrainBrandTrainController::class, 'trainUrlBrainBrand'])->name('url');
    Route::post('{brainBrand}/file', [BrainBrandTrainController::class, 'trainFileBrainBrand'])->name('file');
    Route::post('{brainBrand}/text', [BrainBrandTrainController::class, 'trainTextBrainBrand'])->name('text');
    Route::post('{brainBrand}/qa', [BrainBrandTrainController::class, 'trainQaBrainBrand'])->name('qa');
    Route::post('{brainBrand}/embedding', [BrainBrandTrainController::class, 'generateEmbeddingBrainBrand'])->name('embedding');
    Route::post('', [BrainBrandTrainController::class, 'store'])->name('store');
});
