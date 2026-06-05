<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('supervisors')->updateOrInsert(
            ['email' => 'thonedra.dev@gmail.com'], // Checks if this record exists to prevent duplicates
            [
                'supervisor_name' => 'Thone Dra',
                'password'     => Hash::make('thonedra14223'), // Native Laravel Hashing
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );
    }
}