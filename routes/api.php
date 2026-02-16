<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook Routes
Route::post('/webhooks/paystack', [WebhookController::class, 'paystack'])->name('webhooks.paystack');
Route::post('/webhooks/hubtel', [WebhookController::class, 'hubtel'])->name('webhooks.hubtel');
