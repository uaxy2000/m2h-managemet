<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rule_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->char('lead_id', 36);
            $table->string('triggered_by', 30); // lead_created|lead_updated|manual
            $table->string('status', 20)->default('success'); // success|failed|conflict
            $table->longText('conflict_rules')->nullable(); // JSON array of rule IDs that also matched
            $table->longText('actions_log')->nullable();    // JSON array of action results
            $table->timestamps();

            $table->index(['rule_id', 'lead_id']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rule_runs');
    }
};
