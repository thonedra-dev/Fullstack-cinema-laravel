<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_food_drink_inventory', function (Blueprint $table) {
            $table->dropColumn('stock_count');
        });
    }

    public function down(): void
    {
        Schema::table('branch_food_drink_inventory', function (Blueprint $table) {
            $table->integer('stock_count')->default(0);
        });
    }
};