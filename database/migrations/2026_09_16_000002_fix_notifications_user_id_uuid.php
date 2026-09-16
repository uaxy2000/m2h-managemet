<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // foreignId() created bigint; users table uses UUID — change to varchar(36)
        DB::statement('ALTER TABLE notifications MODIFY COLUMN user_id VARCHAR(36) NOT NULL');

        // Drop the auto-generated bigint FK if it exists
        try {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'notifications'
                  AND COLUMN_NAME = 'user_id'
                  AND REFERENCED_TABLE_NAME = 'users'
            ");
            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE notifications DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        } catch (\Throwable) {}

        // Re-add FK pointing to uuid primary key on users
        DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE notifications DROP FOREIGN KEY notifications_user_id_foreign');
        DB::statement('ALTER TABLE notifications MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
    }
};
