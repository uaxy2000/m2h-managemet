<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('pipeline_id')->constrained();
            $table->foreignUuid('stage_id')->constrained();
            $table->foreignUuid('sub_stage_id')->nullable()->constrained('sub_stages')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('nationality')->nullable();
            $table->string('language')->nullable();
            $table->decimal('potential_value', 15, 2)->nullable();
            $table->decimal('our_commission', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();
            $table->foreignUuid('service_provider_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->boolean('is_duplicate_flag')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
