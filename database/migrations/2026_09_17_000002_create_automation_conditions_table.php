<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->unsignedTinyInteger('group_index')->default(0);
            $table->string('field', 50);      // stage|tag|custom_field|source|assigned_user|country
            $table->string('field_key', 191)->nullable(); // custom_field_id for custom_field type
            $table->string('operator', 30);   // is|is_not|in|not_in|contains|not_contains|is_empty|is_not_empty
            $table->longText('value')->nullable(); // JSON array — always array, even single values
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_conditions');
    }
};
