<?php

namespace Database\Seeders;

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
            "ММТ сбор",
            "На финал турнира",
            "Кубки \ Наградная атрибутика",
            "Приз лучшему игроку",
            "Призовой",
            "Плашки",
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate([
                'name' => $category,
            ]);
        }
    }
}
