<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_time_wallets', function (Blueprint $table) {
            $table->timestamp('last_applied_at')->nullable()->after('available_seconds');
            $table->decimal('drain_rate', 6, 3)->default(1.000)->after('last_applied_at');
            $table->boolean('is_active')->default(true)->after('drain_rate');
        });
    }

    public function down(): void
    {
        Schema::table('user_time_wallets', function (Blueprint $table) {
            $table->dropColumn(['last_applied_at', 'drain_rate', 'is_active']);
        });
    }
};
