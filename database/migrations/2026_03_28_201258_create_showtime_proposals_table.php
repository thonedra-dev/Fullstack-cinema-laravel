<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Showtime proposals now reference hall_id instead of theatre_id.
     *
     * Same reasoning as showtimes: a proposal is submitted by a branch manager
     * for a specific hall in their cinema, not for a global theatre type.
     *
     * Workflow after this base migration, two further migrations will alter this table:
     *   1. alter_showtime_proposals_table_change_dates_to_timestamp
     *      → drops selected_dates / start_time / end_time
     *      → adds start_datetime / end_datetime (full timestamp per slot row)
     *
     *   2. remove_status_from_showtime_proposals
     *      → drops status / admin_note
     *      → those columns now live on showtime_proposal_status table
     *
     * Final schema after all three migrations:
     *   id, manager_id, cinema_id, hall_id, movie_id,
     *   start_datetime, end_datetime, created_at, updated_at
     */
    public function up(): void
    {
        Schema::create('showtime_proposals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manager_id')
                  ->constrained('managers', 'manager_id')
                  ->cascadeOnDelete();

            $table->foreignId('cinema_id')
                  ->constrained('cinemas', 'cinema_id')
                  ->cascadeOnDelete();

            // hall_id replaces theatre_id from the old design
            $table->foreignId('hall_id')
                  ->constrained('halls', 'hall_id')
                  ->cascadeOnDelete();

            $table->foreignId('movie_id')
                  ->constrained('movies', 'movie_id')
                  ->cascadeOnDelete();

            // ── Scheduling fields (will be replaced by the alter migration) ─
            $table->json('selected_dates');
            $table->time('start_time');
            $table->time('end_time');

            // ── Workflow fields (will be removed by remove_status migration) ─
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