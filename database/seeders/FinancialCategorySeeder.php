<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'ФОТ',
            'МАРКЕТИНГ',
            'ПОМЕЩЕНИЕ',
            'НАЛОГИ И КОМИССИИ',
            'ХОЗРАСХОДЫ',
            'РЕМОНТ И ОБОРУДОВАНИЕ',
            'ПРИЗЫ',
            'ПРОЧЕЕ',
        ];

        foreach ($categories as $index => $name) {
            $category = FinancialCategory::query()->firstOrCreate(
                [
                    'parent_id' => null,
                    'name' => $name,
                ],
                [
                    'sort_order' => ($index + 1) * 10,
                ],
            );

            if ($name === 'ФОТ') {
                $category->update([
                    'code' => 'payroll',
                    'is_system' => true,
                ]);
            }
        }

        $payroll = FinancialCategory::query()
            ->where('code', 'payroll')
            ->firstOrFail();

        $financeSortOrder = (int) $payroll->children()
            ->where('name', 'Финансы')
            ->value('sort_order');

        $teamSalaries = FinancialCategory::query()->updateOrCreate(
            ['code' => 'team_salaries'],
            [
                'parent_id' => $payroll->id,
                'name' => 'Зарплаты команды',
                'is_system' => true,
                'sort_order' => $financeSortOrder + 10,
            ],
        );

        FinancialCategory::query()->updateOrCreate(
            ['code' => 'team_event_salaries'],
            [
                'parent_id' => $teamSalaries->id,
                'name' => 'Менеджеры, ведущие, судьи и супервайзеры',
                'is_system' => true,
                'sort_order' => 10,
            ],
        );

        FinancialCategory::query()->updateOrCreate(
            ['code' => 'team_event_other_expenses'],
            [
                'parent_id' => $teamSalaries->id,
                'name' => 'Прочие расходы в игровых вечерах и турнирах',
                'is_system' => true,
                'sort_order' => 20,
            ],
        );
    }
}
