<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cinemas = [
            [
                'name'        => 'X_Gotham',
                'city'        => 'Gotham',
                'contact'     => '09-123456789',
                'address'     => '123 Wayne Tower, Gotham City',
                'description' => 'The premier cinematic experience in the heart of Gotham.',
                'picture'     => 'cinemas/X_Gotham.jpg',
            ],
            [
                'name'        => 'X_Metropolis',
                'city'        => 'Metropolis',
                'contact'     => '09-987654321',
                'address'     => '456 Daily Planet Ave, Metropolis',
                'description' => 'Watch the latest hits in the City of Tomorrow.',
                'picture'     => 'cinemas/X_Metropolis.jpg',
            ],
        ];

        foreach ($cinemas as $cinema) {
            $cityId = DB::table('cities')->where('city_name', $cinema['city'])->value('city_id');

            if ($cityId) {
                // Check if either 'X_Gotham' OR 'X Gotham' exists to avoid duplicate constraint checks
                $exists = DB::table('cinemas')
                    ->where('cinema_name', $cinema['name'])
                    ->orWhere('cinema_name', str_replace('_', ' ', $cinema['name']))
                    ->exists();

                if ($exists) {
                    // Update matching rows based on the variation found
                    DB::table('cinemas')
                        ->where('cinema_name', $cinema['name'])
                        ->orWhere('cinema_name', str_replace('_', ' ', $cinema['name']))
                        ->update([
                            'cinema_name'        => $cinema['name'],
                            'cinema_contact'     => $cinema['contact'],
                            'cinema_address'     => $cinema['address'],
                            'cinema_description' => $cinema['description'],
                            'cinema_picture'     => $cinema['picture'],
                            'city_id'            => $cityId,
                            'updated_at'         => now(),
                        ]);
                } else {
                    // Insert perfectly with all NOT NULL parameters defined
                    DB::table('cinemas')->insert([
                        'cinema_name'        => $cinema['name'],
                        'cinema_contact'     => $cinema['contact'],
                        'cinema_address'     => $cinema['address'],
                        'cinema_description' => $cinema['description'],
                        'cinema_picture'     => $cinema['picture'],
                        'city_id'            => $cityId,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }
        }
    }
}