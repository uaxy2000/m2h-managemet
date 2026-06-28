<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->foreign('sheet_source_id')->references('id')->on('google_sheet_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->dropForeign(['sheet_source_id']);
        });
    }
};
