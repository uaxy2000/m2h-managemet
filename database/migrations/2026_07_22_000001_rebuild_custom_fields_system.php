<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old stub tables (order matters: values first, then fields)
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');

        // Custom field definitions
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 80)->unique();
            $table->string('label', 100);
            $table->enum('type', ['date', 'text', 'select', 'multi_select']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Options for select / multi_select fields
        Schema::create('custom_field_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('custom_field_id');
            $table->string('value', 100);
            $table->string('label', 150);
            $table->boolean('is_exclusive')->default(false);
            $table->longText('meta_aliases')->nullable();   // JSON array of raw Meta answer strings
            $table->unsignedInteger('sort_order')->default(0);
        });

        // Per-lead custom field values
        Schema::create('lead_custom_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('custom_field_id');
            $table->longText('value')->nullable();           // plain string or JSON array for multi_select
            $table->timestamps();
            $table->unique(['lead_id', 'custom_field_id']);
        });

        // Maps a normalized Meta question key → a custom field
        Schema::create('meta_question_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('meta_question_key', 500);
            $table->uuid('custom_field_id');
            $table->index('meta_question_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_question_mappings');
        Schema::dropIfExists('lead_custom_values');
        Schema::dropIfExists('custom_field_options');
        Schema::dropIfExists('custom_fields');
    }
};
