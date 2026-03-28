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
        Schema::create('cinema_movie_quotas', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('movie_id');
    $table->unsignedBigInteger('cinema_id');
    $table->unsignedBigInteger('supervisor_id');

    $table->integer('showtime_slots');
    $table->date('start_date');
    $table->date('maximum_end_date');

    $table->timestamps();

    $table->foreign('movie_id')
        ->references('movie_id')->on('movies')
        ->onDelete('cascade');

    $table->foreign('cinema_id')
        ->references('cinema_id')->on('cinemas')
        ->onDelete('cascade');

    $table->foreign('supervisor_id')
        ->references('supervisor_id')->on('supervisors')
        ->onDelete('cascade');

    $table->unique(['movie_id', 'cinema_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cinema_movie_quotas');
    }
};
