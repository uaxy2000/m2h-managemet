<?php
/**
 * payments ve payment_leads tablolarını oluşturur.
 * URL: https://management.m2h.ge/_migrate_payments.php
 * Çalıştırdıktan sonra sil.
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

header('Content-Type: text/plain; charset=utf-8');

echo "=== PAYMENTS MIGRATION ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// payments
if (Schema::hasTable('payments')) {
    echo "payments tablosu zaten mevcut, atlanıyor.\n";
} else {
    Schema::create('payments', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->constrained('users');
        $table->decimal('amount', 12, 2);
        $table->char('currency', 3)->default('TRY');
        $table->unsignedInteger('lead_count');
        $table->date('paid_at');
        $table->text('note')->nullable();
        $table->foreignUuid('created_by')->constrained('users');
        $table->timestamps();
        $table->index(['user_id', 'paid_at']);
    });
    echo "payments tablosu oluşturuldu.\n";
}

// payment_leads
if (Schema::hasTable('payment_leads')) {
    echo "payment_leads tablosu zaten mevcut, atlanıyor.\n";
} else {
    Schema::create('payment_leads', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
        $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
        $table->unique(['payment_id', 'lead_id']);
        $table->index('lead_id');
    });
    echo "payment_leads tablosu oluşturuldu.\n";
}

echo "\nTamamlandı. Bu dosyayı sil: public/_migrate_payments.php\n";
