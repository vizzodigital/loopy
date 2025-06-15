<?php

declare(strict_types = 1);

use App\Http\Controllers\InstallDefaultAgentsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/agents/install-defaults', InstallDefaultAgentsController::class);
