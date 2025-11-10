<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_accounts', function (Blueprint $table) {
            $table->string('passcode_hash')->nullable()->after('is_active');
            $table->timestamp('passcode_set_at')->nullable()->after('passcode_hash');
        });
    }

    public function down(): void
    {
        Schema::table('time_accounts', function (Blueprint $table) {
            $table->dropColumn(['passcode_hash', 'passcode_set_at']);
        });
    }
};
