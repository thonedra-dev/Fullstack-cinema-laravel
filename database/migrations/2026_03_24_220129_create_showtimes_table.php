<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id('showtime_id');
            $table->unsignedBigInteger('theatre_id');
            $table->unsignedBigInteger('movie_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->foreign('theatre_id')
                  ->references('theatre_id')
                  ->on('theatres')
                  ->cascadeOnDelete();

            $table->foreign('movie_id')
                  ->references('movie_id')
                  ->on('movies')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};