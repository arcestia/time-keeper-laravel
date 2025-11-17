<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_lifetime_upgrades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('stats_cap_steps')->default(0); // each step = +50%
            $table->unsignedInteger('extra_expedition_slots')->default(0);
            $table->boolean('unlimited_energy')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lifetime_upgrades');
    }
};
