<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_manager_notifications_table
 *
 * Table: manager_notifications
 * ─────────────────────────────────────────────
 *  noti_id      bigint PK  auto-increment
 *  manager_id   bigint FK  → managers.manager_id  (cascade delete)
 *  noti_picture varchar    nullable  (portrait_poster filename)
 *  noti_message text       NOT NULL
 *  tag          varchar    NOT NULL  (e.g. 'Movie Rejection By Admin')
 *  created_at   timestamp
 *  updated_at   timestamp
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_notifications', function (Blueprint $table) {

            // Primary key
            $table->bigIncrements('noti_id');

            // Recipient — the branch manager who receives this notification
            $table->unsignedBigInteger('manager_id');

            // Optional thumbnail (portrait poster filename, stored in images/movies/)
            $table->string('noti_picture')->nullable();

            // Notification body — cannot be empty
            $table->text('noti_message');

            // Category tag — e.g. 'Movie Rejection By Admin'
            $table->string('tag');

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('manager_id')
                  ->references('manager_id')
                  ->on('managers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_notifications');
    }
};