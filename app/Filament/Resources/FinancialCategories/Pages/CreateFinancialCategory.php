<?php

namespace App\Filament\Resources\FinancialCategories\Pages;

use App\Filament\Resources\FinancialCategories\FinancialCategoryResource;
use App\Models\FinancialCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateFinancialCategory extends CreateRecord
{
    protected static string $resource =
        FinancialCategoryResource::class;

    protected static ?string $title = 'Новая финансовая категория';

    protected static ?string $breadcrumb = 'Создать';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Создать'),

            $this->getCreateAnotherFormAction()
                ->label('Создать ещё'),

            $this->getCancelFormAction()
                ->label('Отмена'),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Финансовая категория создана';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $parent = empty($data['parent_id'])
            ? null
            : FinancialCategory::query()
                ->findOrFail($data['parent_id']);

        $newCategory = new FinancialCategory($data);

        if (! $newCategory->canMoveTo($parent)) {
            throw ValidationException::withMessages([
                'data.parent_id' =>
                    'Нельзя создать больше трёх уровней вложенности.',
            ]);
        }

        $maxSortOrder = FinancialCategory::query()
            ->where('parent_id', $parent?->id)
            ->max('sort_order');

        $data['sort_order'] = ((int) $maxSortOrder) + 10;

        return $data;
    }
}
