<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rule_id')->constrained('automation_rules');
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->timestamp('triggered_at')->useCurrent();
            $table->text('actions_taken');
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->text('error_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
