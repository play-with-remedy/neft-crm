<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Source;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            'Instagram',
            'Telegram',
            'YouTube',
            'Рекомендации знакомых',
            'Поиск в Google/Яндекс',
            'Другое',
        ];

        foreach ($sources as $name) {
            Source::firstOrCreate([
                'name' => $name,
            ]);
        }
    }
}
