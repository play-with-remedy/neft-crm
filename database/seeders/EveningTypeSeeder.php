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
                'name' => 'Турнир',
                'description' => 'Турниры ММТ',
            ],
            [
                'name' => 'Дополнительные проект',
                'description' => 'Закрытые столы, Городская мафия и др.',
            ],
            [
                'name' => 'Турнир полуспорт',
                'description' => 'Light Cup, Капитанский, Переход ',
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