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
        Schema::create('seats', function (Blueprint $table) {
    $table->id('seat_id');

    $table->unsignedBigInteger('theatre_id');
    $table->foreign('theatre_id')
          ->references('theatre_id')
          ->on('theatres')
          ->onDelete('cascade');

    $table->string('row_label');   // A, B, C...
    $table->integer('seat_number'); // 1,2,3...

    $table->string('seat_type'); // normal / couple / vip

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
