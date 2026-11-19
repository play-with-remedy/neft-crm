<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');

        abort_unless(is_string($secret) && $secret !== ''
            && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        $updateId = $request->input('update_id');
        $chatId = $request->input('message.chat.id');
        $text = $request->input('message.text');

        if (! is_int($updateId) || $updateId < 0 || ! is_int($chatId)
            || $request->input('message.chat.type') !== 'private'
            || $request->boolean('message.from.is_bot') || ! is_string($text)) {
            return response()->json(['ok' => true]);
        }

        $result = Cache::lock("telegram:update-lock:{$updateId}", 30)->get(function () use ($updateId, $chatId, $text, $bot): JsonResponse {
            $processedKey = "telegram:processed:{$updateId}";

            if (Cache::has($processedKey)) {
                return response()->json(['ok' => true]);
            }

            $rateKey = "telegram:chat:{$chatId}";

            if (RateLimiter::tooManyAttempts($rateKey, 20)) {
                return response()->json(['ok' => true]);
            }

            $nickname = preg_replace('/^\s+|\s+$/u', '', $text);

            if ($nickname === '' || mb_strlen($nickname) > 255
                || preg_match('/^\/(?:start|help)(?:@\w+)?(?:\s|$)/iu', $nickname)) {
                $reply = 'Пришлите игровой ник — я проверю, есть ли он в базе клуба. Регистр букв не важен.';
            } else {
                $exists = Player::query()
                    ->whereRaw('LOWER(TRIM(nickname)) = LOWER(?)', [$nickname])
                    ->exists();

                $reply = $exists ? 'Такой ник уже есть в базе.' : 'Такого ника в базе нет.';
            }

            try {
                $bot->sendMessage($chatId, $reply);
            } catch (RuntimeException) {
                return response()->json(['ok' => false], 503);
            }

            RateLimiter::hit($rateKey, 60);
            Cache::put($processedKey, true, now()->addDay());

            return response()->json(['ok' => true]);
        });

        return $result ?? response()->json(['ok' => false], 503);
    }
}
