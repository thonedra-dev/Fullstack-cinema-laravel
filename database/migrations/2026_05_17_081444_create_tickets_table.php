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
    Schema::create('tickets', function (Blueprint $table) {
        $table->bigIncrements('ticket_id');
        $table->unsignedBigInteger('booking_id');
        $table->unsignedBigInteger('showtime_id');
        $table->unsignedBigInteger('seat_id');
        $table->unsignedBigInteger('price_id');
        
        // Add this line to snapshot the price at checkout
        $table->decimal('price_paid', 8, 2); 
        
        $table->timestamps();

        // Foreign Key Constraints
        $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
        $table->foreign('showtime_id')->references('showtime_id')->on('showtimes')->onDelete('cascade');
        $table->foreign('seat_id')->references('seat_id')->on('seats')->onDelete('cascade'); 
        $table->foreign('price_id')->references('price_id')->on('movie_ticket_prices')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};