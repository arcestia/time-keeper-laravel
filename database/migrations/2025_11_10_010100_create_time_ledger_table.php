<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_account_id')->constrained('time_accounts')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit', 'decay', 'adjust']);
            $table->bigInteger('amount_seconds');
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['time_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_ledger');
    }
};
