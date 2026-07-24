<?php

namespace App\Filament\Resources\FinancialCategories\Schemas;

use App\Models\FinancialCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class FinancialCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Select::make('parent_id')
                    ->label('Родительская статья')
                    ->placeholder('Без родителя — первый уровень')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (
                            Builder $query,
                            ?FinancialCategory $record
                        ): Builder {
                            /*
                             * Категории третьего уровня нельзя выбирать
                             * родителями, иначе появится четвёртый уровень.
                             */
                            $query->where(function (Builder $query): void {
                                $query
                                    ->whereNull('parent_id')
                                    ->orWhereHas('parent', function (
                                        Builder $parentQuery
                                    ): void {
                                        $parentQuery->whereNull('parent_id');
                                    });
                            });

                            /*
                             * При редактировании нельзя выбрать:
                             * - саму категорию;
                             * - любого её потомка.
                             */
                            if ($record !== null) {
                                $excludedIds = [
                                    $record->id,
                                    ...$record->descendantIds(),
                                ];

                                $query->whereNotIn('id', $excludedIds);
                            }

                            return $query
                                ->orderBy('sort_order')
                                ->orderBy('name');
                        }
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (FinancialCategory $record): string =>
                            $record->full_name
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->disabled(
                        fn (?FinancialCategory $record): bool =>
                            (bool) $record?->is_system
                    )
                    ->nullable(),
            ]);
    }
}
