<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_time_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('color', 16);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['user_id','color']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_time_tokens');
    }
};
