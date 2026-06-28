<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country');
            $table->string('name');
            $table->enum('type', ['investment', 'real_estate', 'company', 'passive_income', 'digital_nomad']);
            $table->decimal('min_investment', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
