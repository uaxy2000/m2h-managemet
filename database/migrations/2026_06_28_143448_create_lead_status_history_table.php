<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('changed_by')->constrained('users');
            $table->foreignUuid('from_stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->foreignUuid('to_stage_id')->constrained('stages');
            $table->foreignUuid('from_sub_stage_id')->nullable()->constrained('sub_stages')->nullOnDelete();
            $table->foreignUuid('to_sub_stage_id')->nullable()->constrained('sub_stages')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_history');
    }
};
