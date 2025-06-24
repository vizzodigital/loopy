<?php

declare(strict_types = 1);

use App\Http\Controllers\V1\Webhooks\WebhookStoreController;
use App\Http\Controllers\V1\Webhooks\WebhookWhatsAppZApiController;
use App\Http\Controllers\V1\Webhooks\WhatsAppController;
use App\Http\Controllers\V1\Webhooks\WhatsAppGetController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/store/{webhook}', WebhookStoreController::class);
Route::post('/webhook/whatsapp/z-api/{webhook}', WebhookWhatsAppZApiController::class);

//Meta WhatsApp Business API
Route::get('/webhook/whatsapp/official/{webhook}', WhatsAppGetController::class)->name('webhook.whatsapp.official.verify');
Route::post('/webhook/whatsapp/official/{webhook}', WhatsAppController::class)->name('webhook.whatsapp.official.receive');

Route::post('/shopify/gdpr/customer-data-request', fn () => response()->json());
Route::post('/shopify/gdpr/customer-erasure', fn () => response()->json());
Route::post('/shopify/gdpr/shop-erasure', fn () => response()->json());
