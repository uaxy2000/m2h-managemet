<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lead_activities', function (Blueprint $table) {
            $table->boolean('is_read')->default(true)->after('visible_to');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->uuid('read_by')->nullable()->after('read_at');
            $table->foreign('read_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_activities', function (Blueprint $table) {
            $table->dropForeign(['read_by']);
            $table->dropColumn(['is_read', 'read_at', 'read_by']);
        });
    }
};
