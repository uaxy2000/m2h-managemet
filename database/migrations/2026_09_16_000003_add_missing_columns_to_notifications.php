<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw ALTER TABLE + catch to safely add columns that may or may not exist
        try {
            DB::statement('ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER meta');
        } catch (\Throwable) {}

        try {
            DB::statement('ALTER TABLE notifications ADD COLUMN body VARCHAR(255) NULL DEFAULT NULL AFTER title');
        } catch (\Throwable) {}

        try {
            DB::statement('ALTER TABLE notifications ADD COLUMN url VARCHAR(255) NULL DEFAULT NULL AFTER body');
        } catch (\Throwable) {}

        try {
            DB::statement('ALTER TABLE notifications ADD INDEX notifications_user_read_created (user_id, read_at, created_at)');
        } catch (\Throwable) {}
    }

    public function down(): void {}
};
