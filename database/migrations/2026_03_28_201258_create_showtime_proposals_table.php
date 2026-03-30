<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtime_proposals', function (Blueprint $table) {
            $table->id();

            // manager → managers.manager_id
            $table->foreignId('manager_id')
                  ->constrained('managers', 'manager_id')
                  ->onDelete('cascade');

            // cinema → cinemas.cinema_id
            $table->foreignId('cinema_id')
                  ->constrained('cinemas', 'cinema_id')
                  ->onDelete('cascade');

            // theatre → theatres.theatre_id
            $table->foreignId('theatre_id')
                  ->constrained('theatres', 'theatre_id')
                  ->onDelete('cascade');

            // movie → movies.movie_id
            $table->foreignId('movie_id')
                  ->constrained('movies', 'movie_id')
                  ->onDelete('cascade');

            // scheduling input
            $table->json('selected_dates');
            $table->time('start_time');
            $table->time('end_time');

            // workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_proposals');
    }
};