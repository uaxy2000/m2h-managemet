<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('board_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_write')->default(false);
            $table->unique(['board_id', 'role']);
        });

        Schema::create('board_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('card_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->string('role', 50);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_write')->default(false);
            $table->unique(['card_id', 'role']);
        });

        Schema::create('card_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->text('content');
            $table->uuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('card_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->string('title');
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->datetime('due_at')->nullable();
            $table->boolean('is_done')->default(false);
            $table->uuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('board_user_reads', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->datetime('last_read_at');
            $table->primary(['user_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_user_reads');
        Schema::dropIfExists('card_tasks');
        Schema::dropIfExists('card_notes');
        Schema::dropIfExists('card_permissions');
        Schema::dropIfExists('board_cards');
        Schema::dropIfExists('board_permissions');
        Schema::dropIfExists('boards');
    }
};
