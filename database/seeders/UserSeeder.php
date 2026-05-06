<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Remedy',
                'email' => 'nickolayezhenkov@gmail.com',
                'password' => 'remedyN186remedy',
            ],
            [
                'name' => 'Жена',
                'email' => 'jena@test.com',
                'password' => 'jenajena',
            ],
            [
                'name' => 'Фиалка',
                'email' => 'fialka@test.com',
                'password' => 'password',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                ]
            );
        }
    }
}
