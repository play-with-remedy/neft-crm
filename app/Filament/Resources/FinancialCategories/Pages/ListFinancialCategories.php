<?php

namespace App\Filament\Resources\FinancialCategories\Pages;

use App\Filament\Resources\FinancialCategories\FinancialCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialCategories extends ListRecords
{
    protected static string $resource = FinancialCategoryResource::class;

    protected static ?string $title = 'Финансовые категории';

    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать финансовую категорию'),
        ];
    }
}
