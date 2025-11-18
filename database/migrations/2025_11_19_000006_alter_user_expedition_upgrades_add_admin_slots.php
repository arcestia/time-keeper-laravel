<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_expedition_upgrades', function (Blueprint $table) {
            if (!Schema::hasColumn('user_expedition_upgrades', 'admin_permanent_slots')) {
                $table->integer('admin_permanent_slots')->default(0)->after('permanent_slots');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_expedition_upgrades', function (Blueprint $table) {
            if (Schema::hasColumn('user_expedition_upgrades', 'admin_permanent_slots')) {
                $table->dropColumn('admin_permanent_slots');
            }
        });
    }
};
