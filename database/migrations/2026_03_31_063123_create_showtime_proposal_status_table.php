<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtime_proposal_status', function (Blueprint $table) {
            $table->id();
            // Foreign Keys
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('cinema_id');
            $table->unsignedBigInteger('manager_id'); // To know who submitted it
            
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_note')->nullable();
            $table->timestamps();

            // Set up Foreign Key Constraints
            $table->foreign('movie_id')->references('movie_id')->on('movies')->onDelete('cascade');
            $table->foreign('cinema_id')->references('cinema_id')->on('cinemas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_proposal_status');
    }
};
