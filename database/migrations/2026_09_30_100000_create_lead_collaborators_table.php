<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_collaborators', function (Blueprint $table) {
            $table->uuid('lead_id');
            $table->uuid('user_id');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['lead_id', 'user_id']);
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_collaborators');
    }
};
