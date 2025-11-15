<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guild_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('role', 16); // leader, officer, member
            $table->timestamps();

            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['guild_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_members');
    }
};
