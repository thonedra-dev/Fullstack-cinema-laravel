<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('genres')->insert([
            ['genre_name' => 'Action'],
            ['genre_name' => 'Adventure'],
            ['genre_name' => 'Comedy'],
            ['genre_name' => 'Drama'],
            ['genre_name' => 'Horror'],
            ['genre_name' => 'Romance'],
            ['genre_name' => 'Sci-Fi'],
            ['genre_name' => 'Thriller'],
        ]);
    }

    public function down()
    {
        DB::table('genres')->truncate();
    }
};