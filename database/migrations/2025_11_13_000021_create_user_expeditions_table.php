<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_expeditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('expedition_id');
            $table->enum('status', ['pending','active','completed','claimed','cancelled'])->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('base_xp')->default(0);
            $table->json('loot')->nullable();
            $table->timestamps();
            $table->index(['user_id','status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('expedition_id')->references('id')->on('expeditions')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_expeditions');
    }
};
