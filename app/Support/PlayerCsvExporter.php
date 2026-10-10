<?php

namespace App\Support;

use App\Models\Player;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Enumerable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerCsvExporter
{
    public static function downloadAll(): StreamedResponse
    {
        return self::download(
            Player::query()
                ->with(['source', 'firstHost'])
                ->orderBy('nickname')
                ->lazy(500),
            'players-all',
        );
    }

    /** @param Enumerable<int, Player> $players */
    public static function download(Enumerable $players, string $fileNamePrefix = 'players'): StreamedResponse
    {
        $fileName = $fileNamePrefix.'-'.now()->format('Y-m-d-H-i-s').'.csv';

        return response()->streamDownload(
            fn () => self::write($players, 'php://output'),
            $fileName,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /** @param Enumerable<int, Player> $players */
    public static function write(Enumerable $players, string $path): void
    {
        $handle = fopen($path, 'w');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Игровой ник',
            'Имя',
            'Фамилия',
            'Телефон',
            'Telegram',
            'Дата рождения',
            'Пол',
            'Источник',
            'Дата первого посещения',
            'Ведущий',
        ], ';');

        foreach ($players as $player) {
            fputcsv($handle, [
                $player->nickname,
                $player->first_name,
                $player->last_name,
                $player->phone,
                $player->telegram ? '@'.ltrim($player->telegram, '@') : '',
                self::formatBirthday($player),
                match ($player->gender) {
                    'male' => 'м',
                    'female' => 'ж',
                    default => '',
                },
                $player->source?->name,
                self::formatDate($player->first_visit_at),
                $player->firstHost?->nickname,
            ], ';');
        }

        fclose($handle);
    }

    private static function formatBirthday(Player $player): string
    {
        if (! $player->birth_day || ! $player->birth_month) {
            return '';
        }

        return sprintf(
            '%02d.%02d%s',
            $player->birth_day,
            $player->birth_month,
            $player->birth_year ? '.'.$player->birth_year : '',
        );
    }

    private static function formatDate(mixed $date): string
    {
        if (! $date) {
            return '';
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('d.m.Y');
        }

        return Carbon::parse($date)->format('d.m.Y');
    }
}
