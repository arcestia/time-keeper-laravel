<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->unsignedInteger('level')->default(1)->after('is_private');
            $table->unsignedBigInteger('xp')->default(0)->after('level');
            $table->unsignedBigInteger('total_xp')->default(0)->after('xp');
            $table->unsignedBigInteger('next_xp')->default(10000)->after('total_xp');
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->dropColumn(['level','xp','total_xp','next_xp']);
        });
    }
};
