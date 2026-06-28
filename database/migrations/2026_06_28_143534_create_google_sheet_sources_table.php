<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheet_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sheet_id');
            $table->string('tab_name');
            $table->json('column_mapping');
            $table->foreignUuid('pipeline_id')->constrained();
            $table->string('default_source')->nullable();
            $table->string('default_campaign')->nullable();
            $table->string('default_ad_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->enum('last_sync_status', ['success', 'partial', 'failed', 'never'])->default('never');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheet_sources');
    }
};
