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
        Schema::create('food_drink_general', function (Blueprint $table) {
            $table->id(); // Master ID
            $table->string('name'); // e.g., 'Caramel Popcorn', 'Coca-Cola'
            
            // Your tailored cinema category tags
            // Allowed values: 'popcorn_crunch', 'hot_bites', 'beverages', 'combos', 'sweets', 'premium'
            $table->string('category_tag'); 
            
            $table->string('image_path')->nullable();
            
            // Suggested baseline price to guide local managers
            $table->decimal('suggested_price', 8, 2)->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_drink_general');
    }
};