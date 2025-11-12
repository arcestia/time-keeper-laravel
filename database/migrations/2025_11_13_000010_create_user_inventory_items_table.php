<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_item_id');
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['user_id','store_item_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('store_item_id')->references('id')->on('store_items')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_inventory_items');
    }
};
