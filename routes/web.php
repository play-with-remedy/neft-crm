<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('telegram.webhook');
