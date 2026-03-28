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
    Schema::create('branch_managers', function (Blueprint $table) {
        $table->unsignedBigInteger('manager_id');
        $table->unsignedBigInteger('cinema_id');

        // Composite primary key
        $table->primary(['manager_id', 'cinema_id']);

        // Foreign keys
        $table->foreign('manager_id')
              ->references('manager_id')
              ->on('managers')
              ->onDelete('cascade');

        $table->foreign('cinema_id')
              ->references('cinema_id')
              ->on('cinemas')
              ->onDelete('cascade');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_managers');
    }
};
