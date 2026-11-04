<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('purchased_at')
                    ->label('Дата покупки')
                    ->default(now())
                    ->maxDate(now())
                    ->beforeOrEqual(now()->toDateString())
                    ->live()
                    ->required(),

                Select::make('type')
                    ->label('Вид')
                    ->options(Certificate::typeOptions())
                    ->native(false)
                    ->required(),

                Select::make('player_id')
                    ->label('Ник покупателя')
                    ->relationship('purchaser', 'nickname')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                Select::make('seller_host_id')
                    ->label('Кто продал (ник ведущего)')
                    ->relationship('seller', 'nickname', modifyQueryUsing: fn ($query) => $query->orderBy('nickname'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                DatePicker::make('expires_at')
                    ->label('Действует до (включительно)')
                    ->minDate(fn (Get $get): ?string => $get('purchased_at'))
                    ->afterOrEqual('purchased_at')
                    ->live()
                    ->required(),

                Repeater::make('usages')
                    ->label('Даты использования')
                    ->relationship()
                    ->table([
                        TableColumn::make('Дата посещения'),
                    ])
                    ->schema([
                        DatePicker::make('used_at')
                            ->label('Дата использования')
                            ->hiddenLabel()
                            ->minDate(fn (Get $get): ?string => $get('../../purchased_at'))
                            ->maxDate(fn (Get $get): string => min($get('../../expires_at') ?: today()->toDateString(), today()->toDateString()))
                            ->afterOrEqual('../../purchased_at')
                            ->beforeOrEqual(fn (Get $get): string => $get('../../expires_at') ?: today()->toDateString())
                            ->beforeOrEqual(today()->toDateString())
                            ->distinct()
                            ->validationMessages([
                                'distinct' => 'Эта дата использования уже добавлена.',
                                'after_or_equal' => 'Дата использования не может быть раньше покупки.',
                                'before_or_equal' => 'Дата использования не может быть позже срока действия или сегодняшнего дня.',
                            ])
                            ->required(),
                    ])
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->compact()
                    ->addActionLabel('Добавить дату')
                    ->addActionAlignment(Alignment::End)
                    ->addAction(fn (Action $action): Action => $action->color('primary'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
