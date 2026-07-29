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
        Schema::table('meta_form_mappings', function (Blueprint $table) {
            $table->foreignId('wa_template_id')->nullable()->after('assigned_to')
                  ->constrained('wa_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('meta_form_mappings', function (Blueprint $table) {
            $table->dropForeign(['wa_template_id']);
            $table->dropColumn('wa_template_id');
        });
    }
};
