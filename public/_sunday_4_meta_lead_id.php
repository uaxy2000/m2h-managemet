<?php
/**
 * Sunday Step 4: Kommo lead adından Meta lead ID çıkar → leads.meta_lead_id güncelle
 *
 * Kommo lead adları "Facebook №1656255075789224" formatındadır.
 * kommo_leadmap.json (kommo_id → our_uuid) kullanarak eşleştirme yapar.
 *
 * URL: https://management.m2h.ge/_sunday_4_meta_lead_id.php
 */

set_time_limit(300);
ini_set('max_execution_time', 300);

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$base  = "https://{$sub}.kommo.com/api/v4";

function kommoGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200 ? (json_decode($body, true) ?? []) : ['_error' => "HTTP {$code}"];
}

say("=== META LEAD ID UPDATE ===");
say("Başlangıç: " . date('Y-m-d H:i:s'));
say("");

// Lead map yükle (kommo_id → our_uuid)
$mapPath = storage_path('app/kommo_leadmap.json');
if (!file_exists($mapPath)) {
    say("HATA: kommo_leadmap.json bulunamadı.");
    say("Yol: {$mapPath}");
    exit;
}
$leadMap = json_decode(file_get_contents($mapPath), true) ?? [];
say("Lead map yüklendi: " . count($leadMap) . " kayıt");
say("");

$stats = ['updated' => 0, 'already_set' => 0, 'no_meta_id' => 0, 'not_in_map' => 0, 'not_in_db' => 0];
$page = 1;

do {
    $url = "{$base}/leads?page={$page}&limit=250";
    $resp = kommoGet($url, $token);

    if (isset($resp['_error'])) {
        say("HATA sayfa {$page}: {$resp['_error']}");
        break;
    }

    $leads = $resp['_embedded']['leads'] ?? [];
    if (empty($leads)) break;

    say("Sayfa {$page}: " . count($leads) . " Kommo lead işleniyor...");

    foreach ($leads as $kLead) {
        $kommoId = (string) $kLead['id'];
        $name    = $kLead['name'] ?? '';

        // "Facebook №1656255075789224" → meta_lead_id
        if (!preg_match('/№(\d+)/', $name, $m)) {
            $stats['no_meta_id']++;
            continue;
        }
        $metaLeadId = $m[1];

        // Lead map'te var mı?
        $ourId = $leadMap[$kommoId] ?? null;
        if (!$ourId) {
            $stats['not_in_map']++;
            continue;
        }

        // DB'de var mı, meta_lead_id zaten set mi?
        $lead = DB::table('leads')->where('id', $ourId)->first();
        if (!$lead) {
            $stats['not_in_db']++;
            continue;
        }

        if ($lead->meta_lead_id !== null) {
            $stats['already_set']++;
            continue;
        }

        DB::table('leads')
            ->where('id', $ourId)
            ->update(['meta_lead_id' => $metaLeadId, 'updated_at' => now()]);

        $stats['updated']++;
    }

    $hasNext = isset($resp['_links']['next']);
    $page++;
    usleep(300000);
} while ($hasNext);

say("");
say("=== TAMAMLANDI ===");
say("meta_lead_id güncellendi : {$stats['updated']}");
say("Zaten set edilmişti      : {$stats['already_set']}");
say("Kommo'da Meta ID yok     : {$stats['no_meta_id']}  (Kommo'ya direkt girilmiş leadler)");
say("Lead map'te bulunamadı   : {$stats['not_in_map']}");
say("DB'de bulunamadı         : {$stats['not_in_db']}");
say("Bitiş: " . date('Y-m-d H:i:s'));
say("");
say("Sonraki adım: _sunday_3_meta_cf.php (Meta lead ID ile eşleştirme)");
say("Bu dosyayı sil: public/_sunday_4_meta_lead_id.php");

function say(string $msg): void { echo $msg . "\n"; }
