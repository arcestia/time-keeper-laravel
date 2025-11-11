<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_time_wallet_id')->constrained('user_time_wallets')->cascadeOnDelete();
            $table->enum('type', ['decay']);
            $table->bigInteger('amount_seconds'); // negative for decay
            $table->bigInteger('from_seconds')->nullable();
            $table->bigInteger('to_seconds')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_time_wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger');
    }
};
