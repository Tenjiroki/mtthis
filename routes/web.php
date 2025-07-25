<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

Route::post('/webhook', [TelegramWebhookController::class, 'handle']);

Route::get('/', function () {
    return view('welcome');
});
