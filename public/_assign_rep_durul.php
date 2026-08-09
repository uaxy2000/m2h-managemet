<?php
/**
 * "RepDurul" tag'li leadleri durul@m2h.ge kullanıcısına assign eder.
 *
 * URL: https://management.m2h.ge/_assign_rep_durul.php
 *      ?confirm=yes → güncelle
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

header('Content-Type: text/plain; charset=utf-8');

// Kullanıcıyı bul
$durul = DB::table('users')->where('email', 'durul@m2h.ge')->first();
if (!$durul) {
    echo "HATA: durul@m2h.ge bulunamadı.\n";
    exit;
}

// RepDurul tag'ini bul
$tag = DB::table('tags')->where('name', 'RepDurul')->first();
if (!$tag) {
    echo "HATA: 'RepDurul' tag'i bulunamadı.\n";
    // Mevcut tag'lere bak
    $tags = DB::table('tags')->where('name', 'like', '%Durul%')->orWhere('name', 'like', '%Rep%')->get();
    echo "Benzer tag'ler:\n";
    foreach ($tags as $t) echo "  [{$t->id}] {$t->name}\n";
    exit;
}

echo "Kullanıcı : {$durul->name} ({$durul->email}) [{$durul->id}]\n";
echo "Tag       : {$tag->name} [{$tag->id}]\n\n";

// Bu tag'e sahip leadler
$leadIds = DB::table('lead_tags')
    ->where('tag_id', $tag->id)
    ->pluck('lead_id');

$total = $leadIds->count();
echo "RepDurul tag'li lead sayısı: {$total}\n\n";

if ($total === 0) {
    echo "Atanacak lead yok.\n";
    exit;
}

// Preview
$alreadyAssigned = DB::table('leads')
    ->whereIn('id', $leadIds)
    ->where('assigned_to', $durul->id)
    ->count();

$willChange = DB::table('leads')
    ->whereIn('id', $leadIds)
    ->where('assigned_to', '!=', $durul->id)
    ->count();

echo "Zaten durul'a atanmış: {$alreadyAssigned}\n";
echo "Değişecek            : {$willChange}\n\n";

if (($_GET['confirm'] ?? '') !== 'yes') {
    echo "Güncellemek için: ?confirm=yes\n";
    exit;
}

// Güncelle
$now = now();
$updated = 0;

$leadsToUpdate = DB::table('leads')
    ->whereIn('id', $leadIds)
    ->where('assigned_to', '!=', $durul->id)
    ->select('id', 'assigned_to')
    ->get();

foreach ($leadsToUpdate as $lead) {
    DB::table('leads')
        ->where('id', $lead->id)
        ->update(['assigned_to' => $durul->id, 'updated_at' => $now]);

    DB::table('lead_activities')->insert([
        'id'         => (string) Str::uuid(),
        'lead_id'    => $lead->id,
        'causer_id'  => $durul->id,
        'type'       => 'assignment_changed',
        'payload'    => json_encode(['from' => $lead->assigned_to, 'to' => $durul->id]),
        'created_at' => $now,
    ]);

    $updated++;
}

echo "=== TAMAMLANDI ===\n";
echo "Güncellenen lead: {$updated}\n";
echo "Bitiş: " . date('Y-m-d H:i:s') . "\n\n";
echo "Bu dosyayı sil: public/_assign_rep_durul.php\n";
