<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EveningType;

class EveningTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Спорт', 'description' => 'Вечер по правилам спорт'],
            ['name' => 'Турнир', 'description' => 'Турнирный формат'],
            ['name' => 'Полуспорт', 'description' => 'Вечер по правилам полуспорт'],
        ];

        foreach ($types as $type) {
            EveningType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}