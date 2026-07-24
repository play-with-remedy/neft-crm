<?php

namespace App\Filament\Resources\FinancialCategories\Tables;

use App\Models\FinancialCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'parent',
                        'parent.parent',
                    ])
                    ->orderByRaw(
                        "
                        CASE
                            WHEN parent_id IS NULL THEN sort_order
                            WHEN EXISTS (
                                SELECT 1
                                FROM financial_categories AS parent_category
                                WHERE parent_category.id = financial_categories.parent_id
                                  AND parent_category.parent_id IS NULL
                            ) THEN (
                                SELECT parent_category.sort_order
                                FROM financial_categories AS parent_category
                                WHERE parent_category.id = financial_categories.parent_id
                            )
                            ELSE (
                                SELECT root_category.sort_order
                                FROM financial_categories AS parent_category
                                JOIN financial_categories AS root_category
                                    ON root_category.id = parent_category.parent_id
                                WHERE parent_category.id = financial_categories.parent_id
                            )
                        END
                        "
                    )
                    ->orderByRaw(
                        "
                        CASE
                            WHEN parent_id IS NULL THEN 0
                            WHEN EXISTS (
                                SELECT 1
                                FROM financial_categories AS parent_category
                                WHERE parent_category.id = financial_categories.parent_id
                                  AND parent_category.parent_id IS NULL
                            ) THEN sort_order
                            ELSE (
                                SELECT parent_category.sort_order
                                FROM financial_categories AS parent_category
                                WHERE parent_category.id = financial_categories.parent_id
                            )
                        END
                        "
                    )
                    ->orderByRaw(
                        "
                        CASE
                            WHEN parent_id IS NULL THEN 0
                            WHEN EXISTS (
                                SELECT 1
                                FROM financial_categories AS parent_category
                                WHERE parent_category.id = financial_categories.parent_id
                                  AND parent_category.parent_id IS NULL
                            ) THEN 0
                            ELSE sort_order
                        END
                        "
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Структура')
                    ->getStateUsing(
                        fn (FinancialCategory $record): string =>
                            match ($record->level) {
                                1 => $record->name,
                                2 => '└── ' . $record->name,
                                3 => '       └── ' . $record->name,
                                default => $record->name,
                            }
                    )
                    ->weight(
                        fn (FinancialCategory $record): string =>
                            $record->isRoot()
                                ? 'bold'
                                : 'medium'
                    )
                    ->description(
                        fn (FinancialCategory $record): ?string =>
                            $record->isRoot()
                                ? null
                                : $record->full_name
                    )
                    ->searchable(query: function (
                        Builder $query,
                        string $search
                    ): Builder {
                        return $query->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }),

                TextColumn::make('parent.name')
                    ->label('Родитель')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать'),

                DeleteAction::make()
                    ->label('Удалить')
                    ->visible(
                        fn (FinancialCategory $record): bool =>
                            ! $record->is_system
                    )
                    ->modalHeading('Удаление финансовой категории')
                    ->modalDescription('Вы уверены, что хотите удалить эту финансовую категорию?')
                    ->modalSubmitActionLabel('Удалить')
                    ->modalCancelActionLabel('Отмена')
                    ->successNotificationTitle('Финансовая категория удалена'),
            ])
            ->defaultPaginationPageOption(50);
    }
}
