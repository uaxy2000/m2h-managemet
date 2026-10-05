<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_template_users', function (Blueprint $table) {
            $table->unsignedBigInteger('wa_template_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['wa_template_id', 'user_id']);
            $table->foreign('wa_template_id')->references('id')->on('whatsapp_templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('email_template_users', function (Blueprint $table) {
            $table->char('email_template_id', 36);
            $table->unsignedBigInteger('user_id');
            $table->primary(['email_template_id', 'user_id']);
            $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_users');
        Schema::dropIfExists('email_template_users');
    }
};
