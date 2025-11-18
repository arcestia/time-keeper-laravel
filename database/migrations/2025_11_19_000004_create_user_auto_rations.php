<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_auto_rations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('food_enabled')->default(false);
            $table->boolean('water_enabled')->default(false);
            $table->unsignedInteger('food_threshold')->default(50); // percent
            $table->unsignedInteger('water_threshold')->default(50); // percent
            $table->timestamp('access_until')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_auto_rations');
    }
};
