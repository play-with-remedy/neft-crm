<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportEvenings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Экспорт вечеров';

    protected static ?string $title = 'Экспорт вечеров';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.export-evenings';

    public function export(): StreamedResponse
    {
        $fileName = 'evenings-all-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID вечера',
                'Дата проведения',
                'Локация',
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

            Evening::query()
                ->with([
                    'eveningType',
                    'project',
                    'expenses.category',
                    'staff.host',
                    'participants.player',
                    'participants.paymentType',
                ])
                ->orderBy('played_at')
                ->chunk(100, function ($evenings) use ($handle) {
                    foreach ($evenings as $evening) {
                        foreach ($evening->staff as $staff) {
                            fputcsv($handle, [
                                $evening->id,
                                $this->formatDateTime($evening->played_at),
                                $evening->eveningType?->name,
                                $evening->project?->name,
                                'Команда',
                                $staff->host?->nickname,
                                match ($staff->role) {
                                    'host' => 'Ведущий',
                                    'manager' => 'Админ',
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
                                $this->formatDateTime($evening->played_at),
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
                                $this->formatDateTime($evening->played_at),
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
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatDateTime($date): string
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