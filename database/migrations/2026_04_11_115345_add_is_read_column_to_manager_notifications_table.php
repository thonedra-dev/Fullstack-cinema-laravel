<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('manager_notifications', 'is_read')) {
                $table->boolean('is_read')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('manager_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('manager_notifications', 'is_read')) {
                $table->dropColumn('is_read');
            }
        });
    }
};