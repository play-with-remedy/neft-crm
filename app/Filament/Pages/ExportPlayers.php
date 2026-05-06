<?php

namespace App\Filament\Pages;

use App\Models\Player;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportPlayers extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Экспорт игроков';

    protected static ?string $title = 'Экспорт игроков';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.export-players';

    public function export(): StreamedResponse
    {
        $fileName = 'players-all-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

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

            Player::query()
                ->with(['source', 'firstHost'])
                ->orderBy('nickname')
                ->chunk(500, function ($players) use ($handle) {
                    foreach ($players as $player) {
                        fputcsv($handle, [
                            $player->nickname,
                            $player->first_name,
                            $player->last_name,
                            $player->phone,
                            $player->telegram ? '@' . ltrim($player->telegram, '@') : '',
                            $this->formatBirthday($player),
                            match ($player->gender) {
                                'male' => 'м',
                                'female' => 'ж',
                                default => '',
                            },
                            $player->source?->name,
                            $this->formatDate($player->first_visit_at),
                            $player->firstHost?->nickname,
                        ], ';');
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatBirthday(Player $player): string
    {
        if (! $player->birth_day || ! $player->birth_month) {
            return '';
        }

        return sprintf(
            '%02d.%02d%s',
            $player->birth_day,
            $player->birth_month,
            $player->birth_year ? '.' . $player->birth_year : ''
        );
    }

    private function formatDate($date): string
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