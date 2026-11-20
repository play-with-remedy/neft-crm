<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TelegramPlayerConversation
{
    public const ADD = '➕ Добавить игрока';

    public const CHECK = '🔎 Проверить ник';

    public const SAVE = '✅ Сохранить';

    public const CANCEL = '❌ Отмена';

    public function reply(array $message): array
    {
        $chatId = $message['chat']['id'];
        $text = preg_replace('/^\s+|\s+$/u', '', $message['text']);
        $canCreate = $this->canCreate($message);
        $draftKey = "telegram:player-draft:{$chatId}";
        $command = preg_match('/^\/(start|help|add|cancel)(?:@\w+)?(?:\s|$)/iu', $text, $matches)
            ? mb_strtolower($matches[1])
            : null;

        if (! $canCreate) {
            Cache::forget($draftKey);
        }

        if (in_array($command, ['start', 'help'], true)) {
            Cache::forget($draftKey);

            return $this->response("Привет! 👋 Пришли игровой ник — подскажу, есть ли он в клубе.\n\nНики принимаю только на русском. Большие и маленькие буквы для меня одинаковы 🙂"
                .($canCreate ? "\n\nТы также можешь добавить нового игрока — нажми «".self::ADD.'».' : ''), $canCreate);
        }

        if ($command === 'cancel' || $text === self::CANCEL) {
            Cache::forget($draftKey);

            return $this->response('Хорошо, отменили 🙂 Можешь прислать другой ник.', $canCreate);
        }

        if ($command === 'add' || $text === self::ADD) {
            if (! $canCreate) {
                return $this->response('Добавлять игроков пока может только администратор клуба 🙂 А проверить ник можешь прямо здесь — просто пришли его.', false);
            }

            Cache::put($draftKey, ['stage' => 'nickname'], now()->addMinutes(20));

            return $this->response('Давай познакомимся с новым игроком! 🙂 Пришли его ник на русском.', true, [[self::CANCEL]]);
        }

        if ($text === self::CHECK) {
            Cache::forget($draftKey);

            return $this->response('Какой ник проверим? Пришли его на русском 🙂', $canCreate);
        }

        $draft = $canCreate ? Cache::get($draftKey) : null;

        if ($text === self::SAVE) {
            if (! $canCreate) {
                return $this->response('Добавлять игроков пока может только администратор клуба 🙂 А проверить ник можешь прямо здесь — просто пришли его.', false);
            }

            if (($draft['stage'] ?? null) !== 'confirm') {
                return $this->response('Сейчас нечего сохранять 🙂 Сначала выбери «Добавить игрока» и укажи ник.', $canCreate);
            }

            $nickname = $draft['nickname'];
            $result = Cache::lock('telegram:player-create:'.hash('sha256', mb_strtolower($nickname)), 30)->get(function () use ($nickname): array {
                return ['created' => DB::transaction(function () use ($nickname): bool {
                    if ($this->nicknameQuery($nickname)->exists()) {
                        return false;
                    }

                    Player::create(['nickname' => $nickname]);

                    return true;
                })];
            });

            if (! is_array($result)) {
                return $this->response('Этот ник сейчас проверяется. Нажми «Сохранить» ещё раз через пару секунд 🙂', true, [[self::SAVE, self::CANCEL]]);
            }

            Cache::forget($draftKey);

            return $this->response($result['created']
                ? "Готово! Игрок «{$nickname}» теперь в клубе 🎉\nОстальные данные можно заполнить в его карточке."
                : 'Этот ник уже есть в клубе 🙂 Ещё одну карточку создавать не будем.', true);
        }

        if (($draft['stage'] ?? null) === 'confirm') {
            return $this->response('Всё верно? Нажми «Сохранить», чтобы добавить игрока, или «Отмена», чтобы начать заново 🙂', true, [[self::SAVE, self::CANCEL]]);
        }

        if (! $this->isRussianNickname($text)) {
            return $this->response('Ники принимаю только на русском 🙂 Напиши ник русскими буквами — от 1 до 255 символов.', $canCreate,
                $draft ? [[self::CANCEL]] : null);
        }

        $exists = $this->nicknameQuery($text)->exists();

        if (($draft['stage'] ?? null) === 'nickname') {
            if ($exists) {
                return $this->response('Такой ник уже есть в клубе 🙂 Давай выберем другой?', true, [[self::CANCEL]]);
            }

            Cache::put($draftKey, ['stage' => 'confirm', 'nickname' => $text], now()->addMinutes(20));

            return $this->response("Добавим в клуб игрока «{$text}»? 🙂\nПроверь ник и нажми «Сохранить».", true, [[self::SAVE, self::CANCEL]]);
        }

        return $this->response($exists
            ? 'Такой ник уже есть в клубе 🙂'
            : 'Такого ника в клубе пока нет — он свободен! ✨', $canCreate);
    }

    private function canCreate(array $message): bool
    {
        $username = config('services.telegram.player_creator_username');

        return is_string($username) && $username !== ''
            && isset($message['from']['id'], $message['from']['username'])
            && $message['from']['id'] === $message['chat']['id']
            && strcasecmp(ltrim($username, '@'), $message['from']['username']) === 0;
    }

    private function isRussianNickname(string $nickname): bool
    {
        return mb_strlen($nickname) <= 255
            && preg_match('/[А-Яа-яЁё]/u', $nickname)
            && preg_match('/\A[А-Яа-яЁё0-9 \p{P}]+\z/u', $nickname);
    }

    private function nicknameQuery(string $nickname): Builder
    {
        return Player::query()->whereRaw('LOWER(TRIM(nickname)) = LOWER(?)', [$nickname]);
    }

    private function response(string $text, bool $canCreate, ?array $keyboard = null): array
    {
        return [
            'text' => $text,
            'reply_markup' => $canCreate ? [
                'keyboard' => $keyboard ?? [[self::CHECK, self::ADD]],
                'resize_keyboard' => true,
            ] : ['remove_keyboard' => true],
        ];
    }
}
