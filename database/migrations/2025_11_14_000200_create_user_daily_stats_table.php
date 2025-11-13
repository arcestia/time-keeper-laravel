<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // UTC date (YYYY-MM-DD) boundary
            $table->date('date');
            $table->unsignedBigInteger('steps_count')->default(0);
            $table->unsignedInteger('expeditions_completed')->default(0);
            $table->timestamps();

            $table->unique(['user_id','date']);
            $table->index(['date']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_stats');
    }
};
