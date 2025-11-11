<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\CarbonImmutable;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('premiums', 'premium_seconds_active')) {
            // Backfill premium_expires_at using remaining seconds when possible
            $now = CarbonImmutable::now();
            $rows = DB::table('premiums')->select('id','premium_seconds_active','premium_expires_at','lifetime')->get();
            foreach ($rows as $r) {
                $secs = (int)($r->premium_seconds_active ?? 0);
                if ($secs > 0 && !$r->lifetime) {
                    $base = $r->premium_expires_at ? CarbonImmutable::parse($r->premium_expires_at) : $now;
                    if ($base->lt($now)) { $base = $now; }
                    $exp = $base->addSeconds($secs);
                    DB::table('premiums')->where('id', $r->id)->update(['premium_expires_at' => $exp]);
                }
            }
            Schema::table('premiums', function (Blueprint $table) {
                $table->dropColumn('premium_seconds_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('premiums', function (Blueprint $table) {
            $table->bigInteger('premium_seconds_active')->default(0);
        });
    }
};
