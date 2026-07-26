<?php

namespace App\Filament\Resources\FinancialCategories\Tables;

use App\Models\FinancialCategory;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                    ->withCount('children')
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
                        function (FinancialCategory $record): Htmlable {
                            $level = $record->level;
                            $indent = ($level - 1) * 30;
                            $connector = $level === 1 ? '' : '└─';
                            $marker = $record->children_count > 0 ? '▾' : '•';
                            $children = $record->children_count > 0
                                ? '<span style="opacity:.6;font-size:.75rem;">'
                                    . $record->children_count
                                    . '</span>'
                                : '';
                            $weight = $level === 1 ? 700 : 500;

                            return new HtmlString(
                                '<div style="display:flex;align-items:center;gap:.5rem;padding-left:'
                                . $indent
                                . 'px;min-height:2rem;">'
                                . ($connector === ''
                                    ? ''
                                    : '<span style="opacity:.45;font-family:monospace;">'
                                        . $connector
                                        . '</span>')
                                . '<span style="width:1rem;text-align:center;color:rgb(var(--primary-500));">'
                                . $marker
                                . '</span>'
                                . '<span style="font-weight:'
                                . $weight
                                . ';">'
                                . e($record->name)
                                . '</span>'
                                . $children
                                . '</div>'
                            );
                        }
                    )
                    ->html()
                    ->weight(
                        fn (FinancialCategory $record): string =>
                            $record->isRoot()
                                ? 'bold'
                                : 'medium'
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
                Action::make('add_child')
                    ->label('Добавить дочернюю')
                    ->icon('heroicon-o-plus')
                    ->visible(
                        fn (FinancialCategory $record): bool =>
                            $record->level < 3
                    )
                    ->modalHeading(
                        fn (FinancialCategory $record): string =>
                            'Новая категория внутри «'
                            . $record->name
                            . '»'
                    )
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->modalSubmitActionLabel('Создать')
                    ->action(function (
                        array $data,
                        FinancialCategory $record
                    ): void {
                        $maxSortOrder = FinancialCategory::query()
                            ->where('parent_id', $record->id)
                            ->max('sort_order');

                        FinancialCategory::query()->create([
                            'parent_id' => $record->id,
                            'name' => $data['name'],
                            'sort_order' => ((int) $maxSortOrder) + 10,
                        ]);

                        Notification::make()
                            ->title('Дочерняя категория создана')
                            ->success()
                            ->send();
                    }),

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
