<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_insights', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('entity_type', 20); // account, campaign, adset, ad
            $table->string('entity_id', 64);
            $table->string('entity_name', 255)->nullable();
            $table->string('parent_entity_id', 64)->nullable(); // campaign→account, adset→campaign, ad→adset
            $table->decimal('spend', 10, 2)->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('leads_count')->default(0);
            $table->decimal('cpm', 10, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'date'], 'meta_insights_entity_date');
            $table->index(['entity_type', 'date']);
            $table->index(['parent_entity_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_insights');
    }
};
