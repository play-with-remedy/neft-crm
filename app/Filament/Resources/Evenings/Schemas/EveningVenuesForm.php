<?php

namespace App\Filament\Resources\Evenings\Schemas;

use App\Models\Evening;
use App\Models\Hall;
use App\Models\Location;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->options(Location::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->native(false)
                    ->preload()
                    ->dynamicOptions(false)
                    ->searchable(false)
                    ->distinct()
                    ->required()
                    ->exists('locations', 'id')
                    ->validationMessages([
                        'required' => 'Выберите локацию.',
                        'distinct' => 'Эта локация уже добавлена.',
                        'exists' => 'Выберите существующую локацию.',
                    ]),
                ViewField::make('hall_ids')
                    ->label('Залы')
                    ->view('filament.forms.venue-halls-select', [
                        'hallsByLocation' => Hall::query()->orderBy('id')->get(['id', 'name', 'location_id'])
                            ->groupBy('location_id')->map(fn ($halls): array => $halls
                            ->map(fn (Hall $hall): array => ['id' => (string) $hall->id, 'name' => $hall->name])->all())->all(),
                    ])
                    ->default([])
                    ->required()
                    ->rules(fn (Get $get): array => [
                        'array', 'min:1', Rule::exists('halls', 'id')->where('location_id', $get('location_id')),
                    ])
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
