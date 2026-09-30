<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_id');
            $table->uuid('service_provider_id');
            $table->string('currency', 10);
            $table->decimal('client_legal_fees', 12, 2);
            $table->decimal('provider_share', 12, 2);
            $table->decimal('partner_share', 12, 2);
            $table->decimal('commission_pct_legal_fees', 5, 2);
            $table->decimal('commission_pct_investment', 5, 2)->nullable();
            $table->date('effective_from');
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('service_provider_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['program_id', 'service_provider_id', 'effective_from'], 'pp_prog_sp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_pricing');
    }
};
