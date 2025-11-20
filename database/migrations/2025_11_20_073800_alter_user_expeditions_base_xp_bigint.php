<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal
        DB::statement('ALTER TABLE `user_expeditions` MODIFY `base_xp` BIGINT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // Revert to INT UNSIGNED (may truncate if values exceed 4,294,967,295)
        DB::statement('ALTER TABLE `user_expeditions` MODIFY `base_xp` INT UNSIGNED NOT NULL DEFAULT 0');
    }
};
