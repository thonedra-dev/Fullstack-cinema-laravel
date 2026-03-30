<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // Step 1: Drop old columns
    Schema::table('showtime_proposals', function (Blueprint $table) {
        $table->dropColumn(['start_time', 'end_time', 'selected_dates']);
    });

    // Step 2: Add new columns with the correct type
    Schema::table('showtime_proposals', function (Blueprint $table) {
        $table->timestamp('start_datetime')->nullable();
        $table->timestamp('end_datetime')->nullable();
    });
}

public function down(): void
{
    Schema::table('showtime_proposals', function (Blueprint $table) {
        $table->dropColumn(['start_datetime', 'end_datetime']);
        
        $table->time('start_time')->nullable();
        $table->time('end_time')->nullable();
        $table->json('selected_dates')->nullable();
    });
}
};