<?php

// Add these routes to routes/web.php or routes/api.php

use App\Http\Controllers\Payment\WompiController;

// Wompi Payment Routes
Route::group(['prefix' => 'payment/wompi', 'middleware' => 'auth'], function () {
    Route::post('/initiate', [WompiController::class, 'initiatePayment'])->name('payment.wompi.initiate');
    Route::get('/callback', [WompiController::class, 'callback'])->name('payment.wompi.callback');
    Route::get('/status', [WompiController::class, 'getPaymentStatus'])->name('payment.wompi.status');
});

// Webhook route (no auth middleware)
Route::post('/webhook/wompi', [WompiController::class, 'webhook'])->name('payment.wompi.webhook');