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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('company_id');
            $table->string('meta_lead_id', 64)->nullable()->unique()->after('source');
            $table->string('meta_form_id', 64)->nullable()->after('meta_lead_id');
            $table->string('meta_ad_name', 191)->nullable()->after('meta_form_id');
            $table->string('meta_campaign_name', 191)->nullable()->after('meta_ad_name');
            $table->string('meta_platform', 10)->nullable()->after('meta_campaign_name');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['source', 'meta_lead_id', 'meta_form_id', 'meta_ad_name', 'meta_campaign_name', 'meta_platform']);
        });
    }
};
