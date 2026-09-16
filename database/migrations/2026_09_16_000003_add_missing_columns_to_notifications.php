<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add read_at if missing
        if (!Schema::hasColumn('notifications', 'read_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('read_at')->nullable()->after('meta');
            });
        }

        // Add body if missing
        if (!Schema::hasColumn('notifications', 'body')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('body')->nullable()->after('title');
            });
        }

        // Add url if missing
        if (!Schema::hasColumn('notifications', 'url')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('url')->nullable()->after('body');
            });
        }

        // Add composite index if not already there
        try {
            DB::statement('ALTER TABLE notifications ADD INDEX notifications_user_read_created (user_id, read_at, created_at)');
        } catch (\Throwable) {}
    }

    public function down(): void {}
};
