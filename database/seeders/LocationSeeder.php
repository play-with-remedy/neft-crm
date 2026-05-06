<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Мафия клуб "NEFT"',
                'address' => 'ул. Аранская, 8',
                'hall' => 'Манхэттен',
            ],
            [
                'name' => 'Кибитка',
                'address' => 'ул. Октябрьская 18/21',
                'hall' => 16,
            ],
            [
                'name' => 'Loesko bar',
                'address' => 'ул. Железнодорожная, 27',
                'hall' => null,
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}
