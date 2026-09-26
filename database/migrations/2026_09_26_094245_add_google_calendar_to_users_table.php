<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_email')->nullable()->after('email');
            $table->text('google_refresh_token')->nullable()->after('google_email');
            $table->string('google_calendar_id')->nullable()->default('primary')->after('google_refresh_token');
            $table->timestamp('google_connected_at')->nullable()->after('google_calendar_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_email', 'google_refresh_token', 'google_calendar_id', 'google_connected_at']);
        });
    }
};
