<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Host;

class HostSeeder extends Seeder
{
    public function run(): void
    {
        $hosts = [
            'Agrail',
            'Photo Girl',
            'Альбер',
            'Анаконда',
            'Дарек',
            'ДжуДжу',
            'Дора',
            'Звукостафф',
            'Ирбис',
            'Кэт',
            'Красавчик',
            'Магистралка',
            'Маркетинг',
            'Марксту',
            'Миледи',
            'Натали',
            'Ромашка',
            'Росонери',
            'Самас',
            'Феликс',
            'Физик',
            'Фиалка',
            'Хоффнунг',
            'Шаок',
            'Шмектис',
            'Львёнок',
        ];

        foreach ($hosts as $nickname) {
            Host::firstOrCreate([
                'nickname' => $nickname,
            ]);
        }
    }
}