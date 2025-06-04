<?php

declare(strict_types = 1);

use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook/{webhook}', WebhookController::class);

Route::get('/integration/{integration}/status', [IntegrationController::class, 'status']);
