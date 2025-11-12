<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            // Widen stat columns to INT to support premium caps > 100
            $table->integer('energy')->default(0)->change();
            $table->integer('food')->default(0)->change();
            $table->integer('water')->default(0)->change();
            $table->integer('leisure')->default(0)->change();
            $table->integer('health')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            // Revert to smallInteger if previously small; adjust as needed
            $table->smallInteger('energy')->default(0)->change();
            $table->smallInteger('food')->default(0)->change();
            $table->smallInteger('water')->default(0)->change();
            $table->smallInteger('leisure')->default(0)->change();
            $table->smallInteger('health')->default(0)->change();
        });
    }
};
