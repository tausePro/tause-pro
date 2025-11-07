<?php

use App\Extensions\UrlToVideo\System\Http\Controllers\TopViewController;
use App\Extensions\UrlToVideo\System\Http\Controllers\TopviewSettingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth', 'is_not_demo'],
    'prefix'     => 'topview',
    'as'         => 'topview.',
], function () {
    Route::post('store-export-video', [TopViewController::class, 'storeExportVideo'])->name('store-export-video');
    Route::delete('delete-exported-video', [TopViewController::class, 'deleteExportedVideo'])->name('delete-exported-video');

    Route::group([
        'prefix' => 'avatar-marketing-video',
        'as'     => 'avatar-marketing-video.',
    ], function () {
        Route::post('submit-task', [TopViewController::class, 'marketingVideoSubmitTask'])->name('submit-task');
        Route::get('query-task', [TopViewController::class, 'marketingVideoQueryTask'])->name('query-task');
        Route::get('list-scripts', [TopViewController::class, 'marketingVideoListScripts'])->name('list-scripts');
        Route::post('update-script-content', [TopViewController::class, 'marketingVideoUpdateScriptContent'])->name('update-script-content');
        Route::post('export', [TopViewController::class, 'marketingVideoExport'])->name('export');
    });

    Route::group([
        'prefix' => 'general',
        'as'     => 'general.',
    ], function () {
        Route::get('caption-list', [TopViewController::class, 'captionList'])->name('caption-list');
        Route::get('voice-query', [TopViewController::class, 'voiceQuery'])->name('voice-query');
        Route::get('ai-avatar-query', [TopViewController::class, 'aiAvatarQuery'])->name('ai-avatar-query');
        Route::get('notice-url-check', [TopViewController::class, 'noticeUrlCheck'])->name('notice-url-check');
        Route::get('notice-url-handler', [TopViewController::class, 'noticeUrlHandler'])->name('notice-url-handler');
        Route::get('ethnicity-query', [TopViewController::class, 'ethnicityQuery'])->name('ethnicity-query');
    });

    Route::group([
        'prefix' => 'product-avatar',
        'as'     => 'product-avatar.',
    ], function () {
        Route::get('category-query', [TopViewController::class, 'productCategoryQuery'])->name('category-query');
        Route::get('public-products', [TopViewController::class, 'productPubicAvatar'])->name('public-products');
    });

    Route::group([
        'prefix' => 'scraper',
        'as'     => 'scraper.',
    ], function () {
        Route::post('submit', [TopViewController::class, 'submitScraperTask'])->name('submit');
        Route::get('query', [TopViewController::class, 'queryScraperTask'])->name('query');
    });

    Route::group([
        'prefix' => 'upload',
        'as'     => 'upload.',
    ], function () {
        Route::post('upload-files', [TopViewController::class, 'uploadFiles'])->name('upload-files');
        Route::get('get-credential', [TopViewController::class, 'getCredential'])->name('get-credential');
        Route::put('upload-s3', [TopViewController::class, 'uploadS3'])->name('upload-s3');
    });

    Route::group([
        'prefix' => 'video-avatar',
        'as'     => 'video-avatar.',
    ], function () {
        Route::post('submit', [TopViewController::class, 'videoAvatarSubmit'])->name('submit');
        Route::get('query', [TopViewController::class, 'videoAvatarQuery'])->name('query');
    });
});

Route::controller(TopviewSettingController::class)
    ->prefix('dashboard/admin/settings')
    ->middleware(['web', 'auth', 'admin', 'is_not_demo'])
    ->name('dashboard.admin.settings.')->group(function () {
        Route::get('topview', 'index')->name('topview');
        Route::post('topview', 'update')->name('topview.update');
    });
