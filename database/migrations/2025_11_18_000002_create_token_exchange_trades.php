<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('token_exchange_trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buy_order_id');
            $table->unsignedBigInteger('sell_order_id');
            $table->string('color', 16);
            $table->unsignedBigInteger('price_per_unit_seconds');
            $table->unsignedBigInteger('qty');
            $table->unsignedBigInteger('taker_user_id');
            $table->unsignedBigInteger('maker_user_id');
            $table->unsignedBigInteger('fee_seconds')->default(0); // taker fee paid to reserves
            $table->timestamps();
            $table->index(['color','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_exchange_trades');
    }
};
