<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs_catalog', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs_catalog', 'energy_cost')) {
                $table->unsignedTinyInteger('energy_cost')->default(10)->after('cooldown_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs_catalog', function (Blueprint $table) {
            if (Schema::hasColumn('jobs_catalog', 'energy_cost')) {
                $table->dropColumn('energy_cost');
            }
        });
    }
};
