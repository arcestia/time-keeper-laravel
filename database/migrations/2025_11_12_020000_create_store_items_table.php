<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('store_items')) {
            Schema::create('store_items', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->enum('type', ['food','water']);
                $table->text('description')->nullable();
                $table->unsignedInteger('price_seconds');
                $table->unsignedTinyInteger('restore_food')->default(0);
                $table->unsignedTinyInteger('restore_water')->default(0);
                $table->unsignedTinyInteger('restore_energy')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
