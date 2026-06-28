<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('campaign')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('form_name')->nullable();
            $table->uuid('sheet_source_id')->nullable()->index(); // FK to google_sheet_sources added in 2026_06_28_143535
            $table->string('medium')->nullable();
            $table->timestamp('captured_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
