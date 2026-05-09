<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theatres', function (Blueprint $table) {
            $table->id('theatre_id');

            // Theatre name must be globally unique — e.g. "IMAX", "DELUXE", "3D"
            // There are only ~10 master types, shared across all cinema branches.
            $table->string('theatre_name')->unique();

            // Both assets are optional at creation time
            $table->string('theatre_icon')->nullable();
            $table->string('theatre_poster')->nullable();

            // ── No cinema_id ───────────────────────────────────────────────
            // Theatres are now standalone master types, NOT tied to a cinema.
            // The cinema ↔ theatre relationship is expressed through the halls table.
            //
            // ── No timestamps ──────────────────────────────────────────────
            // Theatre model declares  public $timestamps = false;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theatres');
    }
};