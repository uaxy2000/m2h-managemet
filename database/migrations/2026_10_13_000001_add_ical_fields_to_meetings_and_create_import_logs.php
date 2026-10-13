<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('ical_uid')->nullable()->after('google_event_id');
            $table->unsignedInteger('ical_sequence')->nullable()->after('ical_uid');
            $table->string('ical_status', 20)->nullable()->after('ical_sequence');
            $table->index('ical_uid');
        });

        Schema::create('meeting_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20);           // imported | rescheduled | cancelled | skipped
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->char('lead_id', 36)->nullable();
            $table->unsignedBigInteger('email_activity_id')->nullable();
            $table->string('ical_uid')->nullable();
            $table->string('title')->nullable();
            $table->string('organizer')->nullable();
            $table->datetime('old_start')->nullable();
            $table->datetime('new_start')->nullable();
            $table->datetime('new_end')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_import_logs');

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['ical_uid']);
            $table->dropColumn(['ical_uid', 'ical_sequence', 'ical_status']);
        });
    }
};
