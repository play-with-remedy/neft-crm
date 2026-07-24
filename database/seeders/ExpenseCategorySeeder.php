<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $categories = [
            "Сбор ММТ",
            "Финал турнира",
            "Кубки / Наградная атрибутика",
            "Приз лучшему игроку",
            "Призовые",
            "Плашки",
            "Клуб",
            "Налоги",
            "Организатор",
            "Другое",
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate([
                'name' => $category,
            ]);
        }
    }
}
