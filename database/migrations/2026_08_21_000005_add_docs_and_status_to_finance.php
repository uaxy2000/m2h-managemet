<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('document_path', 500)->nullable()->after('description');
            $table->string('status', 20)->default('approved')->after('document_path');
            $table->index('status');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->string('document_path', 500)->nullable()->after('description');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('document_path', 500)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['document_path', 'status']);
        });
        Schema::table('incomes',  fn ($t) => $t->dropColumn('document_path'));
        Schema::table('payments', fn ($t) => $t->dropColumn('document_path'));
    }
};
