<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Showtimes now reference hall_id instead of theatre_id.
     *
     * Why hall_id and not theatre_id:
     *   A showtime is a specific screening happening in a physical hall of a specific cinema.
     *   Two cinemas can both have a DELUXE theatre type, yet run completely different showtimes.
     *   hall_id is the identity that carries both "which cinema" and "which theatre type" together.
     *
     * cinema_id is stored directly (denormalised from hall) for fast, index-friendly queries
     * such as "all showtimes for this cinema" without always joining halls.
     *
     * NOTE: The separate migration  add_cinema_id_to_showtimes_table  is now DELETED.
     *       cinema_id lives here from the very start.
     */
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id('showtime_id');

            $table->unsignedBigInteger('hall_id');
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('cinema_id');

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            // ── Foreign keys ────────────────────────────────────────────────
            $table->foreign('hall_id')
                  ->references('hall_id')
                  ->on('halls')
                  ->cascadeOnDelete();

            $table->foreign('movie_id')
                  ->references('movie_id')
                  ->on('movies')
                  ->cascadeOnDelete();

            // Restrict delete: don't allow a cinema to be deleted while it
            // still has live showtimes (admin must clear schedule first).
            $table->foreign('cinema_id')
                  ->references('cinema_id')
                  ->on('cinemas')
                  ->restrictOnDelete();

            // ── Indexes for common query patterns ───────────────────────────
            $table->index('cinema_id');
            $table->index(['movie_id', 'cinema_id']);

            $table->timestamps();   // Showtime model: public $timestamps = true
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};