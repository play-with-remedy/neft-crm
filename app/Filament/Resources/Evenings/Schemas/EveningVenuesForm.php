<?php

namespace App\Filament\Resources\Evenings\Schemas;

use App\Models\Evening;
use App\Models\Hall;
use App\Models\Location;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Js;
use Illuminate\Validation\Rule;

class EveningVenuesForm
{
    public static function component(): Repeater
    {
        $halls = Hall::query()->orderBy('id')->get(['id', 'name', 'location_id']);
        $hallsByLocation = $halls->groupBy('location_id')->map(fn ($items): array => $items->map(fn (Hall $hall): array => [
            'value' => (string) $hall->id, 'label' => $hall->name, 'isDisabled' => false,
        ])->all())->all();

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
                Select::make('hall_ids')
                    ->label('Залы')
                    ->multiple()
                    ->native(false)
                    ->options($halls->pluck('name', 'id')->all())
                    ->preload()
                    ->dynamicOptions(false)
                    ->searchable(false)
                    ->placeholder('Выберите залы')
                    ->extraAlpineAttributes(fn (Select $component): array => self::hallSelectAttributes($component, $hallsByLocation))
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
                $record->loadMissing('venues.halls');
                $component->state($record->venues->map(fn ($venue): array => [
                    'location_id' => $venue->location_id,
                    'hall_ids' => $venue->halls->modelKeys(),
                ])->all());
            })
            ->saveRelationshipsUsing(fn (Repeater $component, Evening $record) => $record->syncVenueSelections($component->getRawState() ?? []))
            ->columnSpanFull();
    }

    private static function hallSelectAttributes(Select $component, array $hallsByLocation): array
    {
        $options = Js::from($hallsByLocation);
        $locationPath = Js::from(str($component->getStatePath())->beforeLast('.')->append('.location_id')->toString());

        return ['x-init' => <<<JS
            const hallsByLocation = {$options};
            const updateHallOptions = (locationId, clearSelection = false) => {
                if (clearSelection) {
                    state = [];
                    select.state = [];
                }

                const options = hallsByLocation[String(locationId)] ?? [];
                select.options = options;
                select.originalOptions = options;
                select.populateLabelRepositoryFromOptions(options);
                select.updateSelectedDisplay();
                select.renderOptions();

                if (locationId) select.enable();
                else select.disable();
            };

            updateHallOptions(\$wire.\$get({$locationPath}));
            \$watch(() => \$wire.\$get({$locationPath}), (value, previous) => {
                if (String(value ?? '') !== String(previous ?? '')) updateHallOptions(value, true);
            });
            JS];
    }
}
