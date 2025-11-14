<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal for change()
        DB::statement('ALTER TABLE `user_expedition_upgrades` MODIFY COLUMN `temp_expires_at` DATETIME NULL');
    }

    public function down(): void
    {
        // Revert back to TIMESTAMP if needed (may fail if values exceed TIMESTAMP range)
        DB::statement('ALTER TABLE `user_expedition_upgrades` MODIFY COLUMN `temp_expires_at` TIMESTAMP NULL');
    }
};
