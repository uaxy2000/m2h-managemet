<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('field_id')->constrained('custom_fields')->cascadeOnDelete();
            $table->text('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
