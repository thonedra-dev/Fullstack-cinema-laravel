<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_ticket_prices', function (Blueprint $table) {
            $table->id('price_id');

            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('theatre_id');

            $table->string('seat_type'); 
            // e.g. standard, premium, couple

            $table->string('day_type');
            // weekday or weekend

            $table->decimal('price', 8, 2);

            $table->timestamps();

            // Foreign keys
            $table->foreign('movie_id')
                ->references('movie_id')
                ->on('movies')
                ->onDelete('cascade');

            $table->foreign('theatre_id')
                ->references('theatre_id')
                ->on('theatres')
                ->onDelete('cascade');

            // Prevent duplicate pricing rules
            $table->unique([
                'movie_id',
                'theatre_id',
                'seat_type',
                'day_type'
            ], 'unique_ticket_price_rule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_ticket_prices');
    }
};