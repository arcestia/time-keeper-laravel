<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('owner_user_id');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guilds');
    }
};
