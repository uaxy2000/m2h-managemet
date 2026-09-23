<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make changed_by nullable — automation runs have no user context
        Schema::table('lead_status_history', function (Blueprint $table) {
            $table->foreignUuid('changed_by')->nullable()->change();
        });

        // Allow automation rules to skip duplicate-flagged leads
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->boolean('skip_duplicates')->default(false)->after('re_run_mode');
        });
    }

    public function down(): void
    {
        Schema::table('lead_status_history', function (Blueprint $table) {
            $table->foreignUuid('changed_by')->nullable(false)->change();
        });

        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropColumn('skip_duplicates');
        });
    }
};
