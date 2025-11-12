<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('captured_at')->index();
            $table->unsignedBigInteger('reserve_seconds')->default(0);
            $table->unsignedBigInteger('total_wallet_seconds')->default(0);
            $table->unsignedBigInteger('total_bank_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_snapshots');
    }
};
