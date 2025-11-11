<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            if (!Schema::hasColumn('store_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(0)->after('price_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            if (Schema::hasColumn('store_items', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
