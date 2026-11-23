<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Models\Hall;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'sm' => 2])->components([
            TextInput::make('name')->label('Локация')->required()->maxLength(255)
                ->unique(ignoreRecord: true)
                ->mutateStateForValidationUsing(fn ($state): string => trim((string) $state))
                ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                ->validationMessages([
                    'required' => 'Укажите название локации.',
                    'unique' => 'Локация с таким названием уже существует.',
                    'max' => 'Название локации не должно быть длиннее 255 символов.',
                ]),
            TextInput::make('address')->label('Адрес')->required()->maxLength(500)
                ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                ->validationMessages([
                    'required' => 'Укажите адрес локации.',
                    'max' => 'Адрес не должен быть длиннее 500 символов.',
                ]),
            Repeater::make('halls')->label('Залы')->relationship()
                ->table([TableColumn::make('Зал')->hiddenHeaderLabel()])
                ->extraAttributes(['class' => 'fi-location-halls-repeater'])
                ->schema([
                    TextInput::make('name')->label('Зал')->hiddenLabel()->required()->maxLength(255)
                        ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                        ->validationMessages([
                            'required' => 'Укажите название зала.',
                            'max' => 'Название зала не должно быть длиннее 255 символов.',
                        ]),
                ])
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $names = collect($value)->pluck('name')
                        ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
                        ->filter(fn (string $name): bool => $name !== '')->values();

                    if ($names->unique(null, true)->count() !== $names->count()) {
                        $fail('Названия залов внутри одной локации не должны повторяться.');
                    }
                })
                ->defaultItems(1)->minItems(1)->reorderable(false)
                ->compact()
                ->addActionLabel('Добавить')
                ->addActionAlignment(Alignment::End)
                ->addAction(fn (Action $action): Action => $action
                    ->button()->icon(null)->color('primary')->tooltip('Добавить зал'))
                ->deleteAction(fn (Action $action): Action => $action->disabled(function (array $arguments, Repeater $component): bool {
                    $id = $component->getRawState()[$arguments['item']]['id'] ?? null;

                    return filled($id) && Hall::query()->whereKey($id)->whereHas('eveningVenues')->exists();
                }))
                ->validationMessages(['min' => 'Добавьте хотя бы один зал.'])
                ->columnSpanFull(),
        ]);
    }
}
