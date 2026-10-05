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
            $table->char('user_id', 36);
            $table->primary(['wa_template_id', 'user_id']);
        });

        Schema::create('email_template_users', function (Blueprint $table) {
            $table->char('email_template_id', 36);
            $table->char('user_id', 36);
            $table->primary(['email_template_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_users');
        Schema::dropIfExists('whatsapp_template_users');
    }
};
