<?php

namespace App\Filament\Resources\FinancialCategories\Pages;

use App\Filament\Resources\FinancialCategories\FinancialCategoryResource;
use App\Models\FinancialCategory;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditFinancialCategory extends EditRecord
{
    protected static string $resource =
        FinancialCategoryResource::class;

    protected static ?string $breadcrumb = 'Редактировать';

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Сохранить'),

            $this->getCancelFormAction()
                ->label('Отмена'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Финансовая категория сохранена';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var FinancialCategory $category */
        $category = $this->record;

        if ($category->is_system) {
            $data['parent_id'] = $category->parent_id;
        }

        $parent = empty($data['parent_id'])
            ? null
            : FinancialCategory::query()
                ->findOrFail($data['parent_id']);

        if (! $category->canMoveTo($parent)) {
            throw ValidationException::withMessages([
                'data.parent_id' =>
                    'Нельзя выбрать этого родителя: возникнет цикл или глубина станет больше трёх уровней.',
            ]);
        }

        return $data;
    }
}
