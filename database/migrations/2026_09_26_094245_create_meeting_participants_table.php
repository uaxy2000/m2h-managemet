<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->enum('participant_type', ['internal_user', 'lead', 'agent', 'service_provider']);
            $table->string('participant_id');
            $table->string('participant_name');
            $table->string('participant_email')->nullable();
            $table->string('google_event_id')->nullable();
            $table->enum('status', ['invited', 'accepted', 'declined', 'tentative'])->default('invited');
            $table->timestamps();

            $table->index(['meeting_id', 'participant_type', 'participant_id'], 'mp_meeting_type_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
