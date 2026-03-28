<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('managers')->insert([
            'manager_name' => 'moon_lighter',
            'manager_email' => 'moonlighter@test.com',
            'password' => Hash::make('12345678'),
            'manager_passport_pic' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
