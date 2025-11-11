<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_stats')) {
            Schema::create('user_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('energy')->default(100);
                $table->unsignedTinyInteger('food')->default(100);
                $table->unsignedTinyInteger('water')->default(100);
                $table->unsignedTinyInteger('leisure')->default(100);
                $table->unsignedTinyInteger('health')->default(100);
                $table->timestamps();
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
