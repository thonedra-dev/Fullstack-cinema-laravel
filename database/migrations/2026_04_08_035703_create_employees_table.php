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
       Schema::create('employees', function (Blueprint $table) {
    $table->id('employee_id');
    $table->foreignId('cinema_id')->constrained('cinemas', 'cinema_id')->onDelete('cascade');
    $table->string('name');
    $table->string('email_address')->unique();
    $table->string('password'); // Added for login
    $table->boolean('is_email_verified')->default(true); // Default to true for dev
    $table->string('passport_image_path')->nullable();
    $table->enum('gender', ['male', 'female', 'other']);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
