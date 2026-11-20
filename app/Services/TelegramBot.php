<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBot
{
    public function request(string $method, array $parameters = []): mixed
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/{$method}", $parameters);
        } catch (ConnectionException) {
            // Do not include the request URL: it contains the bot token.
            throw new RuntimeException('Telegram API connection failed.');
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException("Telegram API request failed: {$method}.");
        }

        return $response->json('result');
    }

    public function sendMessage(int $chatId, string $text, array $replyMarkup = []): void
    {
        $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            ...($replyMarkup !== [] ? ['reply_markup' => $replyMarkup] : []),
        ]);
    }
}
