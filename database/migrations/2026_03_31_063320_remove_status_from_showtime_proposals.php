<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showtime_proposals', function (Blueprint $table) {
            // Dropping the columns no longer needed here
            $table->dropColumn(['status', 'admin_note']);
        });
    }

    public function down(): void
    {
        Schema::table('showtime_proposals', function (Blueprint $table) {
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
        });
    }
};