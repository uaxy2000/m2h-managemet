<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable(); // FK added after companies in 2026_06_28_143401
            $table->string('name');
            $table->string('email')->length(191)->unique('users_email_unique');
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('imap_host')->nullable();
            $table->string('imap_user')->nullable();
            $table->text('imap_pass_enc')->nullable();
            $table->enum('role', ['super_admin', 'admin', 'member', 'service_provider_user', 'agent_user']);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->length(191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->length(191)->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
