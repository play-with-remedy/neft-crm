<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use Illuminate\Console\Command;
use RuntimeException;

class SetupTelegramBot extends Command
{
    protected $signature = 'telegram:setup {--url= : HTTPS URL of the Telegram webhook}';

    protected $description = 'Подключает Telegram-бота проверки ников к CRM';

    public function handle(TelegramBot $bot): int
    {
        $url = $this->option('url') ?: route('telegram.webhook');
        $secret = config('services.telegram.webhook_secret');

        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $this->error('Для webhook нужен публичный HTTPS URL.');

            return self::FAILURE;
        }

        if (! is_string($secret) || ! preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $secret)) {
            $this->error('Настройте TELEGRAM_WEBHOOK_SECRET: от 1 до 256 символов A–Z, a–z, 0–9, _ или -.');

            return self::FAILURE;
        }

        try {
            $identity = $bot->request('getMe');
            $bot->request('setWebhook', [
                'url' => $url,
                'secret_token' => $secret,
                'allowed_updates' => ['message'],
            ]);
            $bot->request('setMyCommands', [
                'commands' => [
                    ['command' => 'start', 'description' => 'Проверить игровой ник'],
                    ['command' => 'help', 'description' => 'Как пользоваться ботом'],
                ],
            ]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Подключён бот @'.($identity['username'] ?? 'unknown'));
        $this->info('Webhook: '.$url);

        return self::SUCCESS;
    }
}
