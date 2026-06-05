<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('showtime_proposals', function (Blueprint $table) {
            // 1. Drop the columns that are now stored in the parent status table
            // Note: If you have foreign key constraints attached to these columns,
            // you may need to drop the constraints first e.g., $table->dropForeign(['movie_id']);
            $table->dropColumn(['manager_id', 'cinema_id', 'movie_id']);

            // 2. Add the status_id foreign key pointing to showtime_proposal_status
            // We use after('id') to keep the table structure organized neatly at the top
            $table->foreignId('status_id')
                  ->after('id')
                  ->constrained('showtime_proposal_status')
                  ->cascadeOnDelete(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showtime_proposals', function (Blueprint $table) {
            // 1. Drop the foreign key and the column
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');

            // 2. Restore the original columns if you roll back
            $table->unsignedBigInteger('manager_id')->after('id');
            $table->unsignedBigInteger('cinema_id')->after('manager_id');
            $table->unsignedBigInteger('movie_id')->after('cinema_id');
        });
    }
};