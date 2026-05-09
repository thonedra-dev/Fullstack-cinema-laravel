<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Halls are the physical bridge between a cinema branch and a master theatre type.
     *
     * Why this table exists:
     *   Old design had theatres storing cinema_id → 100 cinemas × 10 types = 1 000 redundant rows.
     *   New design:
     *     theatres  → 10 rows   (master type definitions, icons, posters)
     *     cinemas   → 100 rows  (branch info)
     *     halls     → up to 1 000 rows, but ZERO redundant data — just a clean mapping
     *
     * One hall = "Cinema X operates a DELUXE theatre".
     * Seats, showtimes, and proposals all reference hall_id from here.
     *
     * No timestamps — Hall model declares  public $timestamps = false;
     */
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id('hall_id');

            $table->unsignedBigInteger('cinema_id');
            $table->unsignedBigInteger('theatre_id');

            // A cinema can only have ONE hall per theatre type.
            // e.g. Cinema Pavilion KL can only have one IMAX hall.
            $table->unique(['cinema_id', 'theatre_id']);

            $table->foreign('cinema_id')
                  ->references('cinema_id')
                  ->on('cinemas')
                  ->cascadeOnDelete();

            $table->foreign('theatre_id')
                  ->references('theatre_id')
                  ->on('theatres')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};