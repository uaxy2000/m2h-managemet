<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->unsignedInteger('round_robin_index')->default(0)->after('parameters');
        });
    }

    public function down(): void
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->dropColumn('round_robin_index');
        });
    }
};
