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
            $table->string('theatre_name');
            $table->string('theatre_icon');
            $table->string('theatre_poster');

            $table->unsignedBigInteger('cinema_id');
            $table->foreign('cinema_id')
                  ->references('cinema_id')
                  ->on('cinemas')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theatres');
    }
};