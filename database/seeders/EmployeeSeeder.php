<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('employees')->insert([
            ['cinema_id' => 5, 'name' => 'Arjun Sharma', 'email_address' => 'arjun@example.com', 'password' => Hash::make('password123'), 'gender' => 'male'],
            ['cinema_id' => 5, 'name' => 'Li Wei', 'email_address' => 'liwei@example.com', 'password' => Hash::make('password123'), 'gender' => 'male'],
            ['cinema_id' => 5, 'name' => 'Priya Patel', 'email_address' => 'priya@example.com', 'password' => Hash::make('password123'), 'gender' => 'female'],
            ['cinema_id' => 5, 'name' => 'Chen Hao', 'email_address' => 'chenhao@example.com', 'password' => Hash::make('password123'), 'gender' => 'male'],
            ['cinema_id' => 5, 'name' => 'Mei Ling', 'email_address' => 'meiling@example.com', 'password' => Hash::make('password123'), 'gender' => 'female'],
        ]);
    }
}