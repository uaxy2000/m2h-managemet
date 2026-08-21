<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->foreignUuid('category_id')->constrained('transaction_categories');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('TRY');
            $table->text('description')->nullable();
            $table->foreignUuid('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignUuid('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('source_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['date']);
            $table->index(['category_id', 'date']);
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->foreignUuid('category_id')->constrained('transaction_categories');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('TRY');
            $table->text('description')->nullable();
            $table->foreignUuid('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('target_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['date']);
            $table->index(['category_id', 'date']);
        });

        Schema::create('account_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->foreignUuid('from_account_id')->constrained('financial_accounts');
            $table->foreignUuid('to_account_id')->constrained('financial_accounts');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('TRY');
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();

            $table->index('date');
        });

        // Ledger: all account movements. amount is signed (+in, -out).
        Schema::create('account_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 12, 2); // positive = money in, negative = money out
            $table->string('description')->nullable();
            $table->string('movable_type')->nullable();
            $table->uuid('movable_id')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['account_id', 'date']);
            $table->index(['movable_type', 'movable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_movements');
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
    }
};
