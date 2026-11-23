<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use App\Services\TelegramSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot, TelegramSchedule $schedule): JsonResponse
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

        $result = Cache::lock("telegram:update-lock:{$updateId}", 120)->get(function () use ($request, $updateId, $chatId, $bot, $schedule): JsonResponse {
            $processedKey = "telegram:processed:{$updateId}";

            if (Cache::has($processedKey)) {
                return response()->json(['ok' => true]);
            }

            $rateKey = "telegram:chat:{$chatId}";

            if (RateLimiter::tooManyAttempts($rateKey, 20)) {
                return response()->json(['ok' => true]);
            }

            $replyKey = "telegram:schedule-reply:{$updateId}";
            $reply = Cache::get($replyKey);

            if ($reply === null) {
                $reply = Cache::lock("telegram:chat-lock:{$chatId}", 120)->get(function () use ($request, $schedule, $replyKey): array {
                    $reply = [...$schedule->reply($request->input('message')), 'sent' => 0];
                    Cache::put($replyKey, $reply, now()->addDay());

                    return $reply;
                });

                if (! is_array($reply)) {
                    return response()->json(['ok' => false], 503);
                }
            }

            try {
                for ($index = $reply['sent']; $index < count($reply['messages']); $index++) {
                    $bot->sendMessage($chatId, $reply['messages'][$index], $reply['reply_markup']);
                    $reply['sent'] = $index + 1;
                    Cache::put($replyKey, $reply, now()->addDay());
                }
            } catch (RuntimeException) {
                return response()->json(['ok' => false], 503);
            }

            RateLimiter::hit($rateKey, 60);
            Cache::put($processedKey, true, now()->addDay());

            return response()->json(['ok' => true]);
        });

        return $result instanceof JsonResponse ? $result : response()->json(['ok' => false], 503);
    }
}
