<?php

namespace App\Services;

use App\Models\Evening;
use App\Models\EveningVenue;

class TelegramSchedule
{
    public const BUTTON = '📅 Расписание';

    private const MESSAGE_LIMIT = 4000;

    public function reply(array $message): array
    {
        $text = trim($message['text']);
        $command = preg_match('/^\/(start|schedule)(?:@\w+)?(?:\s|$)/iu', $text);
        $requested = $command || in_array(mb_strtolower($text), ['расписание', mb_strtolower(self::BUTTON)], true);

        return [
            'messages' => $requested ? $this->messages() : [
                'Привет! 👋 Здесь можно посмотреть ближайшие игры клуба. Нажми «'.self::BUTTON.'» или отправь /schedule.',
            ],
            'reply_markup' => ['keyboard' => [[self::BUTTON]], 'resize_keyboard' => true],
        ];
    }

    private function messages(): array
    {
        $now = Evening::clubNow();
        $until = $now->copy()->startOfDay()->addDays(7);
        $evenings = Evening::query()
            ->where('played_at', '>=', $now->format('Y-m-d H:i:s'))
            ->where('played_at', '<', $until->format('Y-m-d H:i:s'))
            ->with(['project', 'eveningType', 'venues.location', 'venues.halls'])
            ->orderBy('played_at')->orderBy('id')->get();

        if ($evenings->isEmpty()) {
            return ['На ближайшие 7 дней игр пока нет 🙂 Загляни чуть позже — расписание обновляется.'];
        }

        $heading = '📅 Расписание на '.$now->format('d.m.Y').'–'.$until->copy()->subDay()->format('d.m.Y');
        $messages = [];
        $current = $heading;
        $available = self::MESSAGE_LIMIT - $this->length($heading) - 2;

        foreach ($evenings as $evening) {
            foreach ($this->splitBlock($this->formatEvening($evening), $available) as $block) {
                if ($this->length($current."\n\n".$block) > self::MESSAGE_LIMIT) {
                    $messages[] = $current;
                    $current = $heading;
                }

                $current .= "\n\n".$block;
            }
        }

        $messages[] = $current;

        return $messages;
    }

    private function formatEvening(Evening $evening): string
    {
        $lines = [
            $evening->played_at->format('d.m.Y, H:i'),
            ($evening->project?->name ?? 'Игра клуба').' · '.($evening->eveningType?->name ?? 'Тип игры уточняется'),
        ];

        foreach ($evening->venues as $venue) {
            $lines[] = $this->formatVenue($venue);
        }

        if ($evening->venues->isEmpty()) {
            $lines[] = '📍 Локация уточняется';
        }

        $lines[] = match (true) {
            $evening->price === null => 'Стоимость уточняется',
            (float) $evening->price === 0.0 => 'Бесплатно',
            default => 'Стоимость: '.number_format((float) $evening->price, 2, ',', ' ').' руб.',
        };

        return implode("\n", $lines);
    }

    private function formatVenue(EveningVenue $venue): string
    {
        return '📍 '.$venue->location->name.' — '.($venue->halls->pluck('name')->join('; ') ?: 'Залы уточняются')
            ."\n".$venue->location->address;
    }

    private function length(string $text): int
    {
        return (int) (strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) / 2);
    }

    /** @return array<string> */
    private function splitBlock(string $text, int $limit): array
    {
        $blocks = [];
        $current = '';
        $length = 0;

        foreach (mb_str_split($text) as $character) {
            $units = strlen($character) === 4 ? 2 : 1;

            if ($length + $units > $limit) {
                $blocks[] = $current;
                $current = '';
                $length = 0;
            }

            $current .= $character;
            $length += $units;
        }

        if ($current !== '') {
            $blocks[] = $current;
        }

        return $blocks;
    }
}
