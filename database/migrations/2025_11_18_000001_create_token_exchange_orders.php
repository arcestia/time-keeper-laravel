<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('token_exchange_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('side', ['buy','sell']);
            $table->string('color', 16); // red, blue, green, yellow, black, diamond
            $table->unsignedBigInteger('price_per_unit_seconds'); // tick = 1s
            $table->unsignedBigInteger('qty_total'); // tokens
            $table->unsignedBigInteger('qty_filled')->default(0);
            $table->enum('status', ['open','partial','filled','canceled'])->default('open');
            // holdings reserved per order
            $table->unsignedBigInteger('held_seconds')->default(0); // for buy orders
            $table->unsignedBigInteger('held_tokens')->default(0);  // for sell orders
            $table->timestamps();
            $table->index(['color','side','price_per_unit_seconds']);
            $table->index(['status','color']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_exchange_orders');
    }
};
