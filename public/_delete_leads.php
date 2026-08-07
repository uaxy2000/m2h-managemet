<?php
/**
 * Production lead temizleme scripti.
 * Tüm lead'leri ve bağlı kayıtları siler.
 * Kullanım: https://domain.com/_delete_leads.php
 * ÇALIŞTIRDIKTAN SONRA HEMEN SİL.
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

use Illuminate\Support\Facades\DB;

// Sayıları önce göster
$counts = [
    'leads'                 => DB::table('leads')->count(),
    'lead_activities'       => DB::table('lead_activities')->count(),
    'notes'                 => DB::table('notes')->count(),
    'tasks'                 => DB::table('tasks')->count(),
    'lead_custom_values'    => DB::table('lead_custom_values')->count(),
    'lead_status_histories' => DB::table('lead_status_histories')->count(),
    'lead_tags'             => DB::table('lead_tags')->count(),
    'lead_program'          => DB::table('lead_program')->count(),
];

echo "=== SİLİNECEK KAYITLAR ===\n";
foreach ($counts as $table => $count) {
    echo "  {$table}: {$count}\n";
}
echo "\n";

if (array_sum($counts) === 0) {
    echo "Zaten boş, silinecek kayıt yok.\n";
    exit(0);
}

// Güvenlik: ?confirm=yes parametresi olmadan çalışmaz
if (($_GET['confirm'] ?? '') !== 'yes') {
    echo "ONAY GEREKLİ.\n";
    echo "Silmek için şu URL'yi aç:\n";
    echo "https://management.m2h.ge/_delete_leads.php?confirm=yes\n";
    exit(0);
}

echo "Siliniyor...\n";

// Bağlı tablolar önce (foreign key sırası)
DB::table('lead_tags')->delete();
echo "[OK] lead_tags\n";

DB::table('lead_program')->delete();
echo "[OK] lead_program\n";

DB::table('lead_custom_values')->delete();
echo "[OK] lead_custom_values\n";

DB::table('lead_status_histories')->delete();
echo "[OK] lead_status_histories\n";

DB::table('lead_activities')->delete();
echo "[OK] lead_activities\n";

DB::table('notes')->delete();
echo "[OK] notes\n";

DB::table('tasks')->delete();
echo "[OK] tasks\n";

DB::table('leads')->delete();
echo "[OK] leads\n";

echo "\n=== TAMAMLANDI ===\n";
echo "Tüm lead'ler ve bağlı kayıtlar silindi.\n";
echo "Bu dosyayı hemen sil: public/_delete_leads.php\n";
