<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentType;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $types = [
            'Наличные',
            'Карта',
            'ЕПОС',
            'Сертификат',
            '2 по цене 1',
        ];

        foreach ($types as $type) {
            PaymentType::firstOrCreate([
                'type' => $type,
            ]);
        }
    }
}
