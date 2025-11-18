<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_auto_rations', function (Blueprint $table) {
            $table->json('food_item_keys')->nullable()->after('water_threshold');
            $table->json('water_item_keys')->nullable()->after('food_item_keys');
        });
    }

    public function down(): void
    {
        Schema::table('user_auto_rations', function (Blueprint $table) {
            $table->dropColumn(['food_item_keys','water_item_keys']);
        });
    }
};
