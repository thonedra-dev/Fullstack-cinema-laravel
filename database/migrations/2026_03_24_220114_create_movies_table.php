<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id('movie_id');
            $table->string('movie_title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};