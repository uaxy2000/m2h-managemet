<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meta_form_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('meta_page_id')->constrained('meta_pages')->cascadeOnDelete();
            $table->string('form_id', 64)->nullable();
            $table->string('form_name', 191)->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignUuid('pipeline_id')->constrained('pipelines');
            $table->foreignUuid('stage_id')->constrained('stages');
            $table->json('tag_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_form_mappings');
    }
};
