<?php

namespace App\Filament\Resources\Evenings\Actions;

use App\Models\Evening;
use App\Services\EveningLockService;
use App\Support\FilamentAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

final class EveningLockActions
{
    public static function lock(bool $iconOnly = false): Action
    {
        $action = Action::make('lock')
            ->label('Заблокировать')
            ->icon('heroicon-m-lock-closed')
            ->color('warning')
            ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
            ->visible(fn (Evening $record): bool => FilamentAccess::isSuperAdmin()
                && ! app(EveningLockService::class)->isLocked($record))
            ->modalHeading('Заблокировать вечер')
            ->modalDescription('Обычный админ больше не сможет редактировать или удалить этот вечер.')
            ->modalSubmitActionLabel('Заблокировать')
            ->modalCancelActionLabel('Отмена')
            ->schema([
                Textarea::make('reason')
                    ->label('Причина')
                    ->placeholder('Необязательно')
                    ->maxLength(255)
                    ->rows(3),
            ])
            ->action(function (Evening $record, array $data): void {
                $record->update([
                    'locked_at' => now(),
                    'locked_by_user_id' => Auth::id(),
                    'lock_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
                ]);

                Notification::make()
                    ->title('Вечер заблокирован')
                    ->success()
                    ->send();
            });

        return $iconOnly
            ? $action->iconButton()->size('sm')->tooltip('Заблокировать вечер')
            : $action;
    }

    public static function unlock(bool $iconOnly = false): Action
    {
        $action = Action::make('unlock')
            ->label('Разблокировать')
            ->icon('heroicon-m-lock-open')
            ->color('success')
            ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
            ->visible(fn (Evening $record): bool => FilamentAccess::isSuperAdmin() && filled($record->locked_at))
            ->requiresConfirmation()
            ->modalHeading('Разблокировать вечер')
            ->modalDescription('Индивидуальная блокировка будет снята. Блокировка закрытым периодом, если она действует, сохранится.')
            ->modalSubmitActionLabel('Разблокировать')
            ->modalCancelActionLabel('Отмена')
            ->action(function (Evening $record): void {
                $record->update([
                    'locked_at' => null,
                    'locked_by_user_id' => null,
                    'lock_reason' => null,
                ]);

                Notification::make()
                    ->title('Индивидуальная блокировка снята')
                    ->success()
                    ->send();
            });

        return $iconOnly
            ? $action->iconButton()->size('sm')->tooltip('Разблокировать вечер')
            : $action;
    }
}
