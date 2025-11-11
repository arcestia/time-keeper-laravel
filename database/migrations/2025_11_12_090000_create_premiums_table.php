<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('premiums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->bigInteger('premium_seconds_active')->default(0);
            $table->bigInteger('premium_seconds_accumulated')->default(0);
            $table->boolean('lifetime')->default(false);
            $table->unsignedSmallInteger('weekly_heal_used')->default(0);
            $table->timestamp('weekly_heal_reset_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premiums');
    }
};
