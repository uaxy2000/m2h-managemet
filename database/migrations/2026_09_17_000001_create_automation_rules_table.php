<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old placeholder tables from previous session (incompatible schema)
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automation_rules');

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(10);
            $table->string('re_run_mode', 20)->default('once'); // once | always
            $table->longText('trigger_events'); // JSON array: ["lead_created","lead_updated","manual"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
