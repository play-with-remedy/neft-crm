<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected static ?string $title = 'Статьи расходов';
    protected static ?string $breadcrumb = 'Список';


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая статья расходов')
                ->modalHeading('Новыя статья расходов')
                ->modalSubmitActionLabel('Создать')
                ->createAnother(false)
                ->modalCancelActionLabel('Отмена')
        ];
    }
}
