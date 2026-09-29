<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('imap_port')->default(993)->after('imap_host');
            $table->string('imap_encryption', 10)->default('ssl')->after('imap_port');
            $table->boolean('imap_enabled')->default(false)->after('imap_pass_enc');
            $table->timestamp('imap_last_sync_at')->nullable()->after('imap_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['imap_port', 'imap_encryption', 'imap_enabled', 'imap_last_sync_at']);
        });
    }
};
