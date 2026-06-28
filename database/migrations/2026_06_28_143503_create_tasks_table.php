<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('assigned_to')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->boolean('is_done')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
