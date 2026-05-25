<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EveningType;

class EveningTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Спорт',
                'description' => 'Спортивная мафия',
            ],
            [
                'name' => 'Полуспорт',
                'description' => 'Полуспортивная мафия',
            ],
            [
                'name' => 'Обучение',
                'description' => 'Обучающие проекты',
            ],
            [
                'name' => 'Турниры',
                'description' => 'ММТ',
            ],
            [
                'name' => 'Другое',
                'description' => '',
            ],
        ];

        foreach ($types as $type) {
            EveningType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}