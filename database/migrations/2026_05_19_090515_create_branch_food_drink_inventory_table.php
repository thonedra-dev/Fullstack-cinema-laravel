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
        Schema::create('branch_food_drink_inventory', function (Blueprint $table) {
           
            $table->unsignedBigInteger('cinema_id');
            $table->unsignedBigInteger('food_drink_id');
            $table->decimal('price', 8, 2); 
            $table->integer('stock_count')->default(0);
            $table->string('status')->default('available'); 
            $table->timestamps();

           
            $table->foreign('cinema_id')
                  ->references('cinema_id')
                  ->on('cinemas')
                  ->onDelete('cascade');

            $table->foreign('food_drink_id')
                  ->references('id') 
                  ->on('food_drink_general')
                  ->onDelete('cascade');

            // 4. Set up your Composite Primary Key
            $table->primary(['cinema_id', 'food_drink_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_food_drink_inventory');
    }
};