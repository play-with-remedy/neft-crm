<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $locations = [
                ['name' => 'Neft', 'address' => 'Аранская 8', 'halls' => ['Manhattan', 'Neft', 'Brooklyn']],
                ['name' => 'Кибитка', 'address' => 'Октябрьская 16/28', 'halls' => ['Сицилия, #21', 'Детройит, #30', 'Чикаго, #60', 'Токио, #49']],
            ];

            foreach ($locations as $data) {
                $location = Location::firstOrCreate(['name' => $data['name']], ['address' => $data['address']]);

                foreach ($data['halls'] as $name) {
                    if ($data['name'] === 'Кибитка' && ! $location->halls()->where('name', $name)->exists()) {
                        $oldName = str_replace(', #', ' ', $name);
                        $location->halls()->where('name', $oldName)->update(['name' => $name]);
                    }

                    $location->halls()->firstOrCreate(['name' => $name]);
                }
            }
        });
    }
}
