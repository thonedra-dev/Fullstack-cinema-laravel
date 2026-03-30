<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('managers')->insert([
            [
                'manager_name' => 'John Doe',
                'manager_email' => 'john@example.com',
                'manager_passport_pic' => 'passport1.jpg',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'manager_name' => 'Alice Smith',
                'manager_email' => 'alice@example.com',
                'manager_passport_pic' => 'passport2.jpg',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'manager_name' => 'Bob Lee',
                'manager_email' => 'bob@example.com',
                'manager_passport_pic' => 'passport3.jpg',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'manager_name' => 'Charlie Tan',
                'manager_email' => 'charlie@example.com',
                'manager_passport_pic' => 'passport4.jpg',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('managers')->whereIn('manager_email', [
            'john@example.com',
            'alice@example.com',
            'bob@example.com',
            'charlie@example.com'
        ])->delete();
    }
};