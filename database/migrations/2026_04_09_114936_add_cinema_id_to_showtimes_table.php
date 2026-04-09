<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('cinema_id')->nullable()->after('movie_id');
        });

        DB::statement('
            UPDATE showtimes AS s
            SET cinema_id = t.cinema_id
            FROM theatres AS t
            WHERE s.theatre_id = t.theatre_id
        ');

        $missingRows = DB::table('showtimes')
            ->whereNull('cinema_id')
            ->count();

        if ($missingRows > 0) {
            throw new RuntimeException(
                "Backfill failed: {$missingRows} showtimes rows still have null cinema_id."
            );
        }

        DB::statement('ALTER TABLE showtimes ALTER COLUMN cinema_id SET NOT NULL');

        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreign('cinema_id')
                ->references('cinema_id')
                ->on('cinemas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('cinema_id');
            $table->index(['movie_id', 'cinema_id']);
        });
    }

    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
            $table->dropIndex(['cinema_id']);
            $table->dropIndex(['movie_id', 'cinema_id']);
            $table->dropColumn('cinema_id');
        });
    }
};