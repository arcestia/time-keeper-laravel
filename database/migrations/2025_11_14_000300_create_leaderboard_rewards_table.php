<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leaderboard_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // daily|weekly|monthly
            $table->string('metric'); // steps|exp_completed
            $table->string('period_key'); // e.g., 2025-11-13 (daily), 2025-11-13-7d (weekly end date), 2025-11 (monthly)
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('rank');
            $table->string('token_color');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['period','metric','period_key','user_id'], 'uniq_lb_reward_user');
            $table->index(['period','metric','period_key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_rewards');
    }
};
