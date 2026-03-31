<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showtime_proposal_status', function (Blueprint $table) {
            // This assumes your table is 'managers' and PK is 'manager_id'
            $table->foreign('manager_id')
                  ->references('manager_id')
                  ->on('managers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('showtime_proposal_status', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
    }
};