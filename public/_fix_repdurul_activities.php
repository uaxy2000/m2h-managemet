<?php
/**
 * RepDurul lead düzeltmeleri:
 * 1. "Assigned to Durul (was: Burak)" atama kayıtlarını sil
 * 2. RepDurul taglli leadlerdeki notların sahibini Burak'tan Durul'a çevir
 *
 * URL: https://management.m2h.ge/_fix_repdurul_activities.php
 * Çalıştırdıktan sonra sil.
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== REPDURUL ACTIVITY FIX ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// Durul ve Burak'ı bul
$durul = DB::table('users')->where('email', 'like', '%durul%')->first();
$burak = DB::table('users')->where('email', 'like', '%burak%')->orWhere('email', 'like', '%bzor%')->first();

if (!$durul) {
    echo "HATA: Durul kullanicisi bulunamadi.\n";
    exit(1);
}
if (!$burak) {
    echo "HATA: Burak kullanicisi bulunamadi.\n";
    exit(1);
}

echo "Durul: {$durul->name} ({$durul->id})\n";
echo "Burak: {$burak->name} ({$burak->id})\n\n";

// RepDurul tagini bul
$tag = DB::table('tags')->where('name', 'RepDurul')->first();
if (!$tag) {
    echo "HATA: 'RepDurul' tagi bulunamadi.\n";
    exit(1);
}
echo "Tag: RepDurul ({$tag->id})\n\n";

// RepDurul taglli lead ID'leri
$leadIds = DB::table('lead_tags')
    ->where('tag_id', $tag->id)
    ->pluck('lead_id');

echo "RepDurul taglli lead sayisi: " . $leadIds->count() . "\n\n";

if ($leadIds->isEmpty()) {
    echo "Islem yapilacak lead yok.\n";
    exit(0);
}

$confirm = $_GET['confirm'] ?? '';

if ($confirm !== 'yes') {
    // Sadece önizleme yap
    $assignCount = DB::table('lead_activities')
        ->whereIn('lead_id', $leadIds)
        ->where('type', 'assigned')
        ->where('description', 'like', 'Assigned to Durul%')
        ->count();

    $noteCount = DB::table('lead_activities')
        ->whereIn('lead_id', $leadIds)
        ->where('type', 'note')
        ->where('user_id', $burak->id)
        ->count();

    echo "--- ONIZLEME ---\n";
    echo "Silinecek atama kaydi: {$assignCount}\n";
    echo "Durul'a aktarilacak Burak notu: {$noteCount}\n\n";
    echo "Uygulamak icin: ?confirm=yes ekle\n";
    exit(0);
}

// 1. Atama kayitlarini sil
$deleted = DB::table('lead_activities')
    ->whereIn('lead_id', $leadIds)
    ->where('type', 'assigned')
    ->where('description', 'like', 'Assigned to Durul%')
    ->delete();

echo "Silinen atama kaydi: {$deleted}\n";

// 2. Notlarin sahibini Burak -> Durul yap
$updated = DB::table('lead_activities')
    ->whereIn('lead_id', $leadIds)
    ->where('type', 'note')
    ->where('user_id', $burak->id)
    ->update(['user_id' => $durul->id]);

echo "Durul'a aktarilan not: {$updated}\n";

echo "\nTamamlandi. Bu dosyayi sil: public/_fix_repdurul_activities.php\n";
