<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Pending while handover_at is set and the new assignee has not opened the lead yet
            $table->timestamp('handover_at')->nullable()->after('assigned_to');
            $table->char('handover_from', 36)->nullable()->after('handover_at');
            $table->text('handover_note')->nullable()->after('handover_from');
            $table->timestamp('handover_seen_at')->nullable()->after('handover_note');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['handover_at', 'handover_from', 'handover_note', 'handover_seen_at']);
        });
    }
};
