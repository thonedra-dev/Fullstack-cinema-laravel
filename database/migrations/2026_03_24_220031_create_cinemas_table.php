<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id('cinema_id');         // PK
            $table->string('cinema_name');
            $table->string('cinema_contact');
            $table->string('cinema_address');
            $table->text('cinema_description')->nullable();
            $table->string('cinema_picture')->nullable();

            $table->unsignedBigInteger('city_id');  // FK
            $table->foreign('city_id')
                  ->references('city_id')
                  ->on('cities')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinemas');
    }
};