<?php

namespace App\Filament\Resources\Evenings\Schemas;

use App\Models\Evening;
use App\Models\Hall;
use App\Models\Location;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Validation\Rule;

class EveningVenuesForm
{
    public static function component(): Repeater
    {
        return Repeater::make('venues')
            ->label('Локации')
            ->table([TableColumn::make('Локация'), TableColumn::make('Залы')])
            ->schema([
                Select::make('location_id')
                    ->label('Локация')
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->native(false)
                    ->preload()
                    ->searchable(false)
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->afterStateUpdated(fn (Set $set) => $set('hall_ids', []))
                    ->required()
                    ->exists('locations', 'id')
                    ->validationMessages([
                        'required' => 'Выберите локацию.',
                        'distinct' => 'Эта локация уже добавлена.',
                        'exists' => 'Выберите существующую локацию.',
                    ]),
                Select::make('hall_ids')
                    ->label('Залы')
                    ->multiple()
                    ->options(fn (Get $get): array => Hall::query()
                        ->where('location_id', $get('location_id'))->orderBy('id')->pluck('name', 'id')->all())
                    ->native(false)
                    ->preload()
                    ->searchable(false)
                    ->required()
                    ->minItems(1)
                    ->nestedRecursiveRules([fn (Get $get) => Rule::exists('halls', 'id')->where('location_id', $get('location_id'))])
                    ->validationMessages([
                        'required' => 'Выберите хотя бы один зал.',
                        'min' => 'Выберите хотя бы один зал.',
                        'exists' => 'Выберите зал этой локации.',
                    ]),
            ])
            ->compact()
            ->reorderable(false)
            ->defaultItems(1)
            ->minItems(fn (?Evening $record): int => $record?->exists ? 0 : 1)
            ->addActionLabel('Добавить локацию')
            ->addActionAlignment(Alignment::End)
            ->dehydrated(false)
            ->loadStateFromRelationshipsUsing(function (Repeater $component, Evening $record): void {
                $component->state($record->venues()->with('halls')->get()->map(fn ($venue): array => [
                    'location_id' => $venue->location_id,
                    'hall_ids' => $venue->halls->modelKeys(),
                ])->all());
            })
            ->saveRelationshipsUsing(fn (Repeater $component, Evening $record) => $record->syncVenueSelections($component->getRawState() ?? []))
            ->columnSpanFull();
    }
}
