<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id');
            $table->enum('side', ['a','b']);
            $table->string('type'); // item_inventory|item_storage|time_token|time_balance
            $table->json('payload');
            $table->timestamps();

            $table->foreign('trade_id')->references('id')->on('trades')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_lines');
    }
};
