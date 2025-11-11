<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs_catalog', function (Blueprint $table) {
            $table->boolean('premium_only')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('jobs_catalog', function (Blueprint $table) {
            $table->dropColumn('premium_only');
        });
    }
};
