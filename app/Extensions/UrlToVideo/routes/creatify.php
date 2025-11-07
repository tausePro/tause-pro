<?php

use App\Extensions\UrlToVideo\System\Http\Controllers\CreatifyController;
use App\Extensions\UrlToVideo\System\Http\Controllers\CreatifySettingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth', 'is_not_demo'],
    'prefix'     => 'creatify',
    'as'         => 'creatify.',
], function () {
    // link
    Route::post('generate-link-by-url', [CreatifyController::class, 'generateLinkByUrl'])->name('generate-link-by-url');
    Route::post('generate-link-by-params', [CreatifyController::class, 'generateLinkByParams'])->name('generate-link-by-params');

    // script
    Route::post('generate-script', [CreatifyController::class, 'generateScript'])->name('generate-script');
    Route::get('get-scripts', [CreatifyController::class, 'getScripts'])->name('get-scripts');

    // general
    Route::get('get-avatars', [CreatifyController::class, 'getAvatars'])->name('get-avatars');
    Route::get('get-voices', [CreatifyController::class, 'getVoices'])->name('get-voices');
    Route::get('get-musics', [CreatifyController::class, 'getMusics'])->name('get-musics');

    // generate video
    Route::post('generate-preview-videos', [CreatifyController::class, 'generatePreviewVideos'])->name('generate-preview-videos');
    Route::post('render-final-video', [CreatifyController::class, 'renderFinalVideo'])->name('render-final-video');
    Route::get('get-video-result', [CreatifyController::class, 'getVideoResult'])->name('get-video-result');
});

Route::controller(CreatifySettingController::class)
    ->prefix('dashboard/admin/settings')
    ->middleware(['web', 'auth', 'admin', 'is_not_demo'])
    ->name('dashboard.admin.settings.')->group(function () {
        Route::get('creatify', 'index')->name('creatify');
        Route::post('creatify', 'update')->name('creatify.update');
    });
