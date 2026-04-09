<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('seats')
            ->where('theatre_id', 10)
            ->whereIn('row_label', ['A', 'B', 'C'])
            ->whereBetween('seat_number', [21, 40])
            ->delete();
    }

    public function down(): void
    {
        // Deleted data cannot be safely reconstructed.
    }
};