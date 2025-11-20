<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Expeditions: index by level
        DB::statement('CREATE INDEX IF NOT EXISTS expeditions_level_idx ON expeditions (level)');
        // User expeditions: common filters
        DB::statement('CREATE INDEX IF NOT EXISTS user_expeditions_user_status_id_idx ON user_expeditions (user_id, status, id)');
        DB::statement('CREATE INDEX IF NOT EXISTS user_expeditions_user_status_ends_idx ON user_expeditions (user_id, status, ends_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS user_expeditions_user_status_exp_idx ON user_expeditions (user_id, status, expedition_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS user_expeditions_user_purchased_at_idx ON user_expeditions (user_id, purchased_at)');
    }

    public function down(): void
    {
        // MariaDB does not have IF EXISTS for DROP INDEX with name ambiguity, wrap in try-catch blocks
        try { DB::statement('DROP INDEX expeditions_level_idx ON expeditions'); } catch (\Throwable $e) {}
        try { DB::statement('DROP INDEX user_expeditions_user_status_id_idx ON user_expeditions'); } catch (\Throwable $e) {}
        try { DB::statement('DROP INDEX user_expeditions_user_status_ends_idx ON user_expeditions'); } catch (\Throwable $e) {}
        try { DB::statement('DROP INDEX user_expeditions_user_status_exp_idx ON user_expeditions'); } catch (\Throwable $e) {}
        try { DB::statement('DROP INDEX user_expeditions_user_purchased_at_idx ON user_expeditions'); } catch (\Throwable $e) {}
    }
};
