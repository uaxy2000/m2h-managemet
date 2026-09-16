<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // wa_incoming, lead_assigned, task_due, task_overdue, expense_submitted, expense_decision, lead_stage_changed, note_added, card_assigned
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('url')->nullable();
            $table->json('meta')->nullable(); // {lead_id, task_id, expense_id, card_id, ...}
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
