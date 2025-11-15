<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_a_id');
            $table->unsignedBigInteger('user_b_id');
            $table->string('status')->default('open'); // open, canceled, finalized
            $table->boolean('a_accepted')->default(false);
            $table->boolean('b_accepted')->default(false);
            $table->unsignedBigInteger('canceled_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_a_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_b_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
