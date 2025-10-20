<?php

use App\Extensions\Chatbot\System\Http\Controllers\TriggerManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chatbot Trigger Management Routes
|--------------------------------------------------------------------------
|
| These routes handle the trigger management dashboard for chatbots.
| All routes are protected by authentication and authorization middleware.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Trigger Management Dashboard Routes
    Route::prefix('chatbot/{chatbot}/triggers/management')->name('chatbot.triggers.management.')->group(function () {
        
        // Main dashboard
        Route::get('/', [TriggerManagementController::class, 'index'])->name('index');
        
        // Data endpoint for DataTable
        Route::get('/data', [TriggerManagementController::class, 'getTriggersData'])->name('data');
        
        // Create trigger
        Route::get('/create', [TriggerManagementController::class, 'create'])->name('create');
        Route::post('/create', [TriggerManagementController::class, 'store'])->name('store');
        
        // Edit trigger
        Route::get('/{trigger}/edit', [TriggerManagementController::class, 'edit'])->name('edit');
        Route::put('/{trigger}', [TriggerManagementController::class, 'update'])->name('update');
        
        // Trigger actions
        Route::post('/{trigger}/toggle', [TriggerManagementController::class, 'toggleStatus'])->name('toggle');
        Route::post('/{trigger}/duplicate', [TriggerManagementController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{trigger}', [TriggerManagementController::class, 'destroy'])->name('destroy');
        
        // Testing and preview
        Route::post('/preview', [TriggerManagementController::class, 'preview'])->name('preview');
        Route::post('/{trigger}/test', [TriggerManagementController::class, 'test'])->name('test');
        
        // Templates
        Route::get('/templates', [TriggerManagementController::class, 'getTemplates'])->name('templates');
        Route::post('/apply-template', [TriggerManagementController::class, 'applyTemplate'])->name('apply-template');
    });
    
});