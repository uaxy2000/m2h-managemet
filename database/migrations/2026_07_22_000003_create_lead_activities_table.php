<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('user_id')->nullable();
            $table->string('type', 50);
            $table->text('description');
            $table->string('subject_type', 50)->nullable();
            $table->string('subject_id', 36)->nullable();
            $table->longText('meta')->nullable();
            $table->longText('visible_to')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('lead_id');
            $table->index(['lead_id', 'subject_type', 'subject_id', 'created_at'], 'la_debounce_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
