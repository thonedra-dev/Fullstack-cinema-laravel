<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trailers', function (Blueprint $table) {
            $table->bigIncrements('trailer_id'); // PK for trailers

            $table->unsignedBigInteger('movie_id'); // FK (same name as movies PK)

            $table->string('youtube_url'); // store full YouTube link

            $table->string('type')->nullable(); 
            

            $table->timestamps();

            // FK constraint
            $table->foreign('movie_id')
                  ->references('movie_id')
                  ->on('movies')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trailers');
    }
};
