<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theatre_services', function (Blueprint $table) {
            $table->unsignedBigInteger('theatre_id');
            $table->unsignedBigInteger('service_id');

            $table->foreign('theatre_id')
                  ->references('theatre_id')
                  ->on('theatres')
                  ->cascadeOnDelete();

            $table->foreign('service_id')
                  ->references('service_id')
                  ->on('services')
                  ->cascadeOnDelete();

            $table->primary(['theatre_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theatre_services');
    }
};