<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Gotham', 
            'Metropolis', 
            'Noxus', 
            'Ionia', 
            'Queen', 
            'Star'
        ];

        foreach ($cities as $city) {
            DB::table('cities')->updateOrInsert(
                ['city_name' => $city], // Unique check to prevent duplicates
                [
                    'city_state' => $city,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}