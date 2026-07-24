<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'ФОТ',
                'code' => 'payroll',
                'is_system' => true,
                'sort_order' => 10,
                'children' => [
                    [
                        'name' => 'Руководство',
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Управляющий', 'sort_order' => 1],
                        ],
                    ],
                    [
                        'name' => 'Финансы',
                        'code' => 'payroll_finance',
                        'is_system' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Бухгалтер клубный', 'sort_order' => 1],
                            ['name' => 'Отчетность клуба, Финансист', 'sort_order' => 2],
                            ['name' => 'Таблицы отчет', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'name' => 'Зарплаты команды',
                        'code' => 'team_salaries',
                        'is_system' => true,
                        'sort_order' => 12,
                        'children' => [
                            [
                                'name' => 'Менеджеры, ведущие, судьи и супервайзеры',
                                'code' => 'team_event_salaries',
                                'is_system' => true,
                                'sort_order' => 10,
                            ],
                            [
                                'name' => 'Прочие расходы в игровых вечерах и турнирах',
                                'code' => 'team_event_other_expenses',
                                'is_system' => true,
                                'sort_order' => 20,
                            ],
                        ],
                    ],
                    ['name' => 'Руководители направлений', 'sort_order' => 13],
                    ['name' => 'Администраторы', 'sort_order' => 14],
                    ['name' => 'Поддержка чистоты', 'sort_order' => 15],
                    ['name' => 'Отчетность и аналитика', 'sort_order' => 16],
                    ['name' => 'Разное ', 'sort_order' => 17],
                ],
            ],
            [
                'name' => 'МАРКЕТИНГ',
                'sort_order' => 20,
                'children' => [
                    ['name' => 'Социальные сети', 'sort_order' => 1],
                    [
                        'name' => 'Реклама',
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Таргетированная реклама', 'sort_order' => 1],
                            ['name' => 'Релакс бай', 'sort_order' => 2],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'ПОМЕЩЕНИЕ',
                'sort_order' => 30,
                'children' => [
                    [
                        'name' => 'Кибитка',
                        'sort_order' => 10,
                        'children' => [
                            ['name' => 'Аренда', 'sort_order' => 10],
                        ],
                    ],
                    ['name' => 'Клуб', 'sort_order' => 20],
                ],
            ],
            [
                'name' => 'НАЛОГИ И КОМИССИИ',
                'sort_order' => 40,
                'children' => [
                    ['name' => 'Налоги', 'sort_order' => 10],
                    ['name' => 'Банковские комиссии', 'sort_order' => 20],
                ],
            ],
            [
                'name' => 'ХОЗРАСХОДЫ',
                'sort_order' => 50,
                'children' => [
                    ['name' => 'Расходники', 'sort_order' => 10],
                    ['name' => 'Бытовая химия', 'sort_order' => 20],
                    ['name' => 'Прочие расходники', 'sort_order' => 30],
                ],
            ],
            [
                'name' => 'РЕМОНТ И ОБОРУДОВАНИЕ',
                'sort_order' => 60,
                'children' => [
                    ['name' => 'Ремонт клуба (с января на 6 месяцев)', 'sort_order' => 10],
                    ['name' => 'Насос старый клуб (помывка)', 'sort_order' => 20],
                    ['name' => 'Починка колонки', 'sort_order' => 30],
                ],
            ],
            [
                'name' => 'ПРИЗЫ',
                'sort_order' => 70,
                'children' => [
                    ['name' => 'Призовой фанки', 'sort_order' => 10],
                    ['name' => 'ММТ фанки', 'sort_order' => 20],
                ],
            ],
            [
                'name' => 'ПРОЧЕЕ',
                'sort_order' => 80,
                'children' => [
                    ['name' => 'Тимбилдинг Миледи (расходник)', 'sort_order' => 10],
                ],
            ],
        ];

        $seed = function (
            array $items,
            ?FinancialCategory $parent = null
        ) use (&$seed): void {
            foreach ($items as $item) {
                $children = $item['children'] ?? [];
                unset($item['children']);

                $lookup = filled($item['code'] ?? null)
                    ? ['code' => $item['code']]
                    : [
                        'parent_id' => $parent?->id,
                        'name' => $item['name'],
                    ];

                $category = FinancialCategory::query()
                    ->firstOrNew($lookup);

                $category->fill([
                    'parent_id' => $parent?->id,
                    'name' => $item['name'],
                    'code' => $item['code'] ?? null,
                    'is_system' => $item['is_system'] ?? false,
                    'sort_order' => $item['sort_order'],
                ]);

                $category->save();

                $seed($children, $category);
            }
        };

        $seed($categories);
    }
}
