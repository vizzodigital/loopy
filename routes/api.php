<?php

declare(strict_types = 1);

use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\V1\Webhooks\WebhookStoreController;
use App\Http\Controllers\V1\Webhooks\WebhookWhatsAppZApiController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook/store/{webhook}', WebhookStoreController::class);
Route::post('/webhook/whatsapp/z-api/{webhook}', WebhookWhatsAppZApiController::class);

// Route::get('/integration/{integration}/status', [IntegrationController::class, 'status']);
// Route::post('/webhook/whatsapp/{webhook}', WebhookController::class);
// Route::post('/whatsapp/official/webhook/{integration}', [WhatsAppWebhookController::class, 'handleOfficialWebhook']);
