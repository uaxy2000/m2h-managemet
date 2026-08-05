<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('todo_list_members', function (Blueprint $table) {
            $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->primary(['todo_list_id', 'user_id']);
        });

        Schema::create('todo_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('completed_by')->nullable();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $table->datetime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('todo_list_boards', function (Blueprint $table) {
            $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->primary(['todo_list_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_list_boards');
        Schema::dropIfExists('todo_list_items');
        Schema::dropIfExists('todo_list_members');
        Schema::dropIfExists('todo_lists');
    }
};
