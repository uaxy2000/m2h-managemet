<?php
/**
 * Durul'a assign edilmis tum leadlerdeki Burak notlarini Durul'a aktarir.
 * URL: https://management.m2h.ge/_fix_durul_notes.php
 * Calistirdiktan sonra sil.
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== DURUL NOTE TRANSFER ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

$durul = DB::table('users')->where('email', 'like', '%durul%')->first();
$burak = DB::table('users')->where('email', 'like', '%burak%')->orWhere('email', 'like', '%bzor%')->first();

if (!$durul || !$burak) {
    echo "HATA: Kullanici bulunamadi.\n";
    echo "Durul: " . ($durul ? $durul->name : 'YOK') . "\n";
    echo "Burak: " . ($burak ? $burak->name : 'YOK') . "\n";
    exit(1);
}

echo "Durul: {$durul->name} ({$durul->id})\n";
echo "Burak: {$burak->name} ({$burak->id})\n\n";

// Durul'a assign edilmis tum lead ID'leri
$leadIds = DB::table('leads')
    ->where('assigned_to', $durul->id)
    ->pluck('id');

echo "Durul'a assign edilmis lead sayisi: " . $leadIds->count() . "\n";

$noteCount = DB::table('lead_activities')
    ->whereIn('lead_id', $leadIds)
    ->where('type', 'note')
    ->where('user_id', $burak->id)
    ->count();

echo "Aktarilacak not sayisi (Burak -> Durul): {$noteCount}\n\n";

if (($_GET['confirm'] ?? '') !== 'yes') {
    echo "Uygulamak icin: ?confirm=yes ekle\n";
    exit(0);
}

$updated = DB::table('lead_activities')
    ->whereIn('lead_id', $leadIds)
    ->where('type', 'note')
    ->where('user_id', $burak->id)
    ->update(['user_id' => $durul->id]);

echo "Aktarilan not: {$updated}\n";
echo "\nTamamlandi. Bu dosyayi sil: public/_fix_durul_notes.php\n";
