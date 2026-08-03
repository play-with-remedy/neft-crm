<?php

namespace App\Support;

use App\Models\Evening;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EveningCsvExporter
{
    private const RELATIONS = [
        'eveningType',
        'project',
        'expenses.category',
        'staff.host',
        'participants.player',
        'participants.paymentType',
    ];

    public static function downloadAll(): StreamedResponse
    {
        return self::downloadQuery(
            Evening::query()->orderBy('played_at'),
            'evenings-all-' . now()->format('Y-m-d-H-i-s') . '.csv',
        );
    }

    public static function download(Evening $evening): StreamedResponse
    {
        $date = $evening->played_at?->format('Y-m-d') ?? 'without-date';

        return self::downloadQuery(
            Evening::query()->whereKey($evening->getKey()),
            "evening-{$evening->getKey()}-{$date}.csv",
        );
    }

    private static function downloadQuery(Builder $query, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            self::writeHeader($handle);

            $query
                ->with(self::RELATIONS)
                ->chunkById(100, function ($evenings) use ($handle): void {
                    foreach ($evenings as $evening) {
                        self::writeEvening($handle, $evening);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function writeHeader($handle): void
    {
        fputcsv($handle, [
            'ID вечера',
            'Дата проведения',
            'Тип вечера',
            'Проект',
            'Тип записи',
            'Имя',
            'Роль',
            'Зарплата',
            'Статья расхода',
            'Сумма расхода',
            'Тип оплаты',
            'Сумма оплаты',
            'Новый игрок',
            'Полная оплата',
            'Примечание',
        ], ';');
    }

    private static function writeEvening($handle, Evening $evening): void
    {
        foreach ($evening->staff as $staff) {
            fputcsv($handle, [
                $evening->id,
                self::formatDateTime($evening->played_at),
                $evening->eveningType?->name,
                $evening->project?->name,
                'Команда',
                $staff->host?->nickname,
                match ($staff->role) {
                    'host' => 'Ведущий',
                    'admin' => 'Админ',
                    'manager' => 'Менеджер',
                    'supervisor' => 'Супервайзер',
                    default => $staff->role,
                },
                $staff->salary,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ], ';');
        }

        foreach ($evening->participants as $participant) {
            fputcsv($handle, [
                $evening->id,
                self::formatDateTime($evening->played_at),
                $evening->eveningType?->name,
                $evening->project?->name,
                'Игрок',
                $participant->player?->nickname,
                '',
                '',
                '',
                '',
                $participant->paymentType?->type,
                $participant->paid_amount,
                $participant->is_new_player ? 'Да' : 'Нет',
                $participant->is_full_payment ? 'Да' : 'Нет',
                $participant->note,
            ], ';');
        }

        foreach ($evening->expenses as $expense) {
            fputcsv($handle, [
                $evening->id,
                self::formatDateTime($evening->played_at),
                $evening->eveningType?->name,
                $evening->project?->name,
                'Расход',
                '',
                '',
                '',
                $expense->category?->name,
                $expense->amount,
                '',
                '',
                '',
                '',
                '',
            ], ';');
        }
    }

    private static function formatDateTime($date): string
    {
        if (! $date) {
            return '';
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('d.m.Y H:i');
        }

        return Carbon::parse($date)->format('d.m.Y H:i');
    }
}
