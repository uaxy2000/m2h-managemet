<?php
/**
 * Sunday Step 2: Kommo lead import (sayfa sayfa)
 *
 * ?page=1..4  → lead'leri import et
 * ?step=notes → notları import et
 * ?step=events → stage geçiş geçmişini import et (lead_status_history)
 *
 * Lead map: storage/app/kommo_leadmap.json
 */

set_time_limit(120);
ini_set('max_execution_time', 120);

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

header('Content-Type: text/plain; charset=utf-8');

$token   = env('KOMMO_TOKEN');
$sub     = env('KOMMO_SUBDOMAIN');
$base    = "https://{$sub}.kommo.com/api/v4";
$mapFile = storage_path('app/kommo_leadmap.json');

// ── Stage mapping ─────────────────────────────────────────────────────────
$MAIN = 'a2231030-9124-4a1d-a647-84cf4fb7f277';
$COLD = 'a25182a1-4b78-4249-8c46-17ee529c7fb6';
$stageMap = [
    13154123 => [
        101431363 => [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'], // Gelen müşteriler → LEAD RECEIVED
        101792603 => [$MAIN, 'a223394e-b26a-4d3a-b95b-e98014f0d8db'], // NO CONTACT YET
        101431367 => [$MAIN, 'a25fb42f-ea73-46ac-b457-7e7816537170'], // FIRST CONTACT
        102333639 => [$MAIN, 'a2663250-d9b6-4bf4-abc8-5cacf738486e'], // DISCOVERY CALL
        104144407 => [$MAIN, 'a2663266-e3b6-42cd-b998-a479287c5256'], // LEAD REGISTERED
        101431371 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'], // MEETING SET → MEETING 1
        101431375 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'], // MEETING 1 DONE → MEETING 1
        101431379 => [$MAIN, 'a2719f9f-9895-497a-8bce-12d9ab5e38a7'], // CONTRACT NEGOTIATIONS
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'], // Closed-won → WON
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'], // Closed-lost → LOST
    ],
    13270595 => [
        102332047 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Incoming leads → Cold Start
        102332051 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // COLD START
        102332055 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Offer made
        102332059 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Negotiation
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'], // Won → Main Line WON
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'], // Lost → Main Line LOST
    ],
];

// Kommo stage ID → our stage UUID (flat map for events import)
$flatStageMap = [];
foreach ($stageMap as $stages) {
    foreach ($stages as $kStageId => [$pipelineId, $ourStageId]) {
        $flatStageMap[$kStageId] = $ourStageId;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function kommoGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 30]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 204) return [];
    if ($code !== 200) return ['_error' => "HTTP {$code}: " . substr($body ?? '', 0, 200)];
    return json_decode($body, true) ?? [];
}

function say(string $msg): void { echo $msg . "\n"; }

function getOrCreateTag(string $name, string $groupId, array &$cache): string {
    $key = mb_strtolower(trim($name));
    if (isset($cache[$key])) return $cache[$key];
    $existing = DB::table('tags')->whereRaw('LOWER(name) = ?', [$key])->value('id');
    if ($existing) { $cache[$key] = $existing; return $existing; }
    $id = (string) Str::uuid();
    DB::table('tags')->insert(['id' => $id, 'name' => trim($name), 'color' => '#64748b', 'tag_group_id' => $groupId]);
    $cache[$key] = $id;
    return $id;
}

function loadLeadMap(string $f): array { return file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : []; }
function saveLeadMap(array $m, string $f): void { file_put_contents($f, json_encode($m)); }

// ── Routing ───────────────────────────────────────────────────────────────
$step = $_GET['step'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : null;

if (!$page && !$step) {
    $existing = loadLeadMap($mapFile);
    say("Kullanım:");
    say("  ?page=1..4     → lead'leri import et");
    say("  ?step=notes    → notları import et");
    say("  ?step=events   → stage geçiş geçmişini import et");
    say("");
    say("Mevcut lead map: " . count($existing) . " kayıt");
    exit;
}

// ── Ortak kurulum ─────────────────────────────────────────────────────────
$ourUsers    = DB::table('users')->select('id', 'email')->get()->keyBy('email');
$burakId     = $ourUsers['burak@m2h.ge']?->id ?? $ourUsers['bzorer@gmail.com']?->id
    ?? DB::table('users')->whereIn('role', ['super_admin', 'admin'])->orderBy('created_at')->value('id');
$userMap     = [14808127 => $burakId, 15069907 => $ourUsers['can@m2h.ge']?->id ?? null];
$defaultUser = $burakId;
$internalCompanyId = DB::table('companies')->where('type', 'internal')->orderBy('created_at')->value('id');

$kommoGroup = DB::table('tag_groups')->where('name', 'Kommo Tags')->first();
if (!$kommoGroup) {
    $kommoGroupId = (string) Str::uuid();
    DB::table('tag_groups')->insert(['id' => $kommoGroupId, 'name' => 'Kommo Tags']);
} else {
    $kommoGroupId = $kommoGroup->id;
}

$tagCache       = DB::table('tags')->select('id', 'name')->get()->keyBy(fn($t) => mb_strtolower($t->name))->map(fn($t) => $t->id)->toArray();
$existingPhones = DB::table('leads')->whereNotNull('phone')->pluck('id', 'phone')->toArray();

// ════════════════════════════════════════════════════════
// ADIM: Lead sayfası
// ════════════════════════════════════════════════════════
if ($page) {
    say("=== SAYFA {$page} ===");
    say("Başlangıç: " . date('Y-m-d H:i:s'));

    $leadMap = loadLeadMap($mapFile);
    $stats   = ['created' => 0, 'skipped' => 0, 'tags' => 0, 'errors' => 0];

    $resp = kommoGet("{$base}/leads?with=contacts,tags&limit=250&page={$page}", $token);
    if (isset($resp['_error'])) { say("HATA: " . $resp['_error']); exit(1); }

    $kLeads  = $resp['_embedded']['leads'] ?? [];
    $hasNext = isset($resp['_links']['next']);
    say(count($kLeads) . " lead alındı.");

    // Main contact ID per lead
    $leadToContactId = [];
    foreach ($kLeads as $kl) {
        $cId = null;
        foreach ($kl['_embedded']['contacts'] ?? [] as $c) {
            if ($c['is_main'] ?? false) { $cId = $c['id']; break; }
        }
        if (!$cId) $cId = $kl['_embedded']['contacts'][0]['id'] ?? null;
        $leadToContactId[$kl['id']] = $cId;
    }

    // Batch contact fetch (20'li gruplar)
    $allContactIds  = array_values(array_filter(array_unique($leadToContactId)));
    $contactDetails = [];
    say("Contact fetch (" . count($allContactIds) . " contact)...");
    foreach (array_chunk($allContactIds, 20) as $chunk) {
        $qs   = implode('&', array_map(fn($id) => "filter[id][]={$id}", $chunk));
        $cRes = kommoGet("{$base}/contacts?{$qs}&with=custom_fields_values", $token);
        usleep(200000);
        foreach ($cRes['_embedded']['contacts'] ?? [] as $c) {
            $phone = $email = null;
            foreach ($c['custom_fields_values'] ?? [] as $cf) {
                if (($cf['field_code'] ?? '') === 'PHONE') $phone = $cf['values'][0]['value'] ?? null;
                if (($cf['field_code'] ?? '') === 'EMAIL') $email = $cf['values'][0]['value'] ?? null;
            }
            $contactDetails[$c['id']] = ['name' => $c['name'] ?? null, 'phone' => $phone, 'email' => $email];
        }
    }

    say("Lead'ler işleniyor...");
    foreach ($kLeads as $kl) {
        $kId    = $kl['id'];
        $cId    = $leadToContactId[$kId] ?? null;
        $contact = $cId ? ($contactDetails[$cId] ?? []) : [];

        $fullName  = trim($contact['name'] ?? "Kommo #{$kId}");
        $phone     = isset($contact['phone']) ? preg_replace('/\s+/', '', $contact['phone']) : null;
        $email     = $contact['email'] ?? null;
        $parts     = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?: 'Unknown';
        $lastName  = $parts[1] ?? null;

        $mapped    = $stageMap[$kl['pipeline_id']][$kl['status_id']] ?? [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'];
        [$ourPipeline, $ourStage] = $mapped;

        $ourUser  = $userMap[$kl['responsible_user_id'] ?? 0] ?? $defaultUser;
        $tagNames = array_column($kl['_embedded']['tags'] ?? [], 'name');
        $source   = in_array('Meta Campaign', $tagNames) ? 'meta_ad' : 'manual';

        if ($phone && isset($existingPhones[$phone])) {
            $leadMap[$kId] = $existingPhones[$phone];
            $stats['skipped']++;
            continue;
        }
        if (isset($leadMap[$kId])) { $stats['skipped']++; continue; }

        $ourLeadId = (string) Str::uuid();
        $createdAt = $kl['created_at'] ? date('Y-m-d H:i:s', $kl['created_at']) : now()->toDateTimeString();

        try {
            DB::table('leads')->insert([
                'id' => $ourLeadId, 'first_name' => $firstName, 'last_name' => $lastName,
                'phone' => $phone, 'email' => $email, 'source' => $source,
                'pipeline_id' => $ourPipeline, 'stage_id' => $ourStage,
                'assigned_to' => $ourUser, 'company_id' => $internalCompanyId,
                'created_at' => $createdAt, 'updated_at' => $createdAt,
            ]);
        } catch (\Throwable $e) {
            say("INSERT HATA [{$kId}]: " . $e->getMessage());
            $stats['errors']++;
            continue;
        }

        if ($phone) $existingPhones[$phone] = $ourLeadId;
        $leadMap[$kId] = $ourLeadId;
        $stats['created']++;

        foreach ($tagNames as $tn) {
            if (!trim($tn)) continue;
            try {
                $tagId = getOrCreateTag($tn, $kommoGroupId, $tagCache);
                DB::table('lead_tags')->insertOrIgnore(['lead_id' => $ourLeadId, 'tag_id' => $tagId]);
                $stats['tags']++;
            } catch (\Throwable $e) { say("TAG HATA: " . $e->getMessage()); }
        }
    }

    saveLeadMap($leadMap, $mapFile);
    say("\n=== SAYFA {$page} TAMAMLANDI ===");
    say("Oluşturuldu : {$stats['created']}");
    say("Atlandı     : {$stats['skipped']}");
    say("Tag         : {$stats['tags']}");
    say("Hata        : {$stats['errors']}");
    say("Toplam map  : " . count($leadMap));
    say($hasNext ? "\nSonraki: ?page=" . ($page + 1) : "\nTüm sayfalar bitti! Sonraki: ?step=notes");
    exit;
}

// ════════════════════════════════════════════════════════
// ADIM: Notlar
// ════════════════════════════════════════════════════════
if ($step === 'notes') {
    say("=== NOTLAR ===");
    say("Başlangıç: " . date('Y-m-d H:i:s'));

    $leadMap = loadLeadMap($mapFile);
    if (empty($leadMap)) { say("Lead map boş!"); exit(1); }
    say("Lead map: " . count($leadMap) . " kayıt");

    $stats = ['notes' => 0, 'errors' => 0];
    foreach (array_chunk(array_keys($leadMap), 20) as $i => $chunk) {
        say("Chunk " . ($i + 1) . "/" . ceil(count($leadMap) / 20) . "...");
        $qs   = implode('&', array_map(fn($id) => "filter[entity_id][]={$id}", $chunk));
        $nRes = kommoGet("{$base}/leads/notes?{$qs}&limit=250", $token);
        usleep(200000);
        if (isset($nRes['_error'])) { say("HATA: " . $nRes['_error']); $stats['errors']++; continue; }

        foreach ($nRes['_embedded']['notes'] ?? [] as $note) {
            if ($note['note_type'] !== 'common') continue;
            $text = trim($note['params']['text'] ?? '');
            if ($text === '') continue;
            $ourLeadId = $leadMap[$note['entity_id']] ?? null;
            if (!$ourLeadId) continue;
            $noteUser = $userMap[$note['created_by'] ?? 0] ?? $defaultUser;
            $noteAt   = $note['created_at'] ? date('Y-m-d H:i:s', $note['created_at']) : now()->toDateTimeString();
            try {
                DB::table('notes')->insert([
                    'id' => (string) Str::uuid(), 'lead_id' => $ourLeadId,
                    'created_by' => $noteUser, 'content' => $text,
                    'visibility' => 'internal', 'created_at' => $noteAt,
                ]);
                $stats['notes']++;
            } catch (\Throwable $e) { say("NOT HATA: " . $e->getMessage()); $stats['errors']++; }
        }
    }

    say("\n=== NOTLAR TAMAMLANDI ===");
    say("Not       : {$stats['notes']}");
    say("Hata      : {$stats['errors']}");
    say("Sonraki   : ?step=events");
    exit;
}

// ════════════════════════════════════════════════════════
// ADIM: Stage geçiş geçmişi (events)
// ════════════════════════════════════════════════════════
if ($step === 'events') {
    say("=== STAGE GEÇİŞ GEÇMİŞİ ===");
    say("Başlangıç: " . date('Y-m-d H:i:s'));

    $leadMap = loadLeadMap($mapFile);
    if (empty($leadMap)) { say("Lead map boş!"); exit(1); }
    say("Lead map: " . count($leadMap) . " kayıt");

    $stats = ['inserted' => 0, 'skipped' => 0, 'errors' => 0];

    // Kommo events API — sayfalı çek
    $page = 1;
    do {
        say("\nEvents sayfa {$page}...");
        $resp = kommoGet("{$base}/events?filter[type][]=lead_status_changed&limit=100&page={$page}", $token);
        usleep(200000);

        if (isset($resp['_error'])) { say("HATA: " . $resp['_error']); break; }

        $events  = $resp['_embedded']['events'] ?? [];
        $hasNext = isset($resp['_links']['next']);
        say(count($events) . " event alındı.");

        foreach ($events as $ev) {
            $kLeadId   = $ev['entity_id'] ?? null;
            $ourLeadId = $leadMap[$kLeadId] ?? null;
            if (!$ourLeadId) { $stats['skipped']++; continue; }

            // from_stage
            $kFromStage = $ev['value_before'][0]['lead_status']['id'] ?? null;
            $kToStage   = $ev['value_after'][0]['lead_status']['id']  ?? null;
            if (!$kToStage) { $stats['skipped']++; continue; }

            $ourToStage   = $flatStageMap[$kToStage]   ?? null;
            $ourFromStage = $kFromStage ? ($flatStageMap[$kFromStage] ?? null) : null;
            if (!$ourToStage) { $stats['skipped']++; continue; }

            $changedBy = $userMap[$ev['created_by'] ?? 0] ?? $defaultUser;
            $changedAt = $ev['created_at'] ? date('Y-m-d H:i:s', $ev['created_at']) : now()->toDateTimeString();

            try {
                DB::table('lead_status_history')->insert([
                    'id'            => (string) Str::uuid(),
                    'lead_id'       => $ourLeadId,
                    'changed_by'    => $changedBy,
                    'from_stage_id' => $ourFromStage,
                    'to_stage_id'   => $ourToStage,
                    'changed_at'    => $changedAt,
                ]);
                $stats['inserted']++;
            } catch (\Throwable $e) {
                say("HATA: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        $page++;
    } while ($hasNext && $page <= 50);

    say("\n=== EVENTS TAMAMLANDI ===");
    say("Kaydedildi : {$stats['inserted']}");
    say("Atlandı    : {$stats['skipped']} (map'siz lead veya bilinmeyen stage)");
    say("Hata       : {$stats['errors']}");
    say("Bitiş      : " . date('Y-m-d H:i:s'));
    say("\nSonraki    : _sunday_3_meta_cf.php (Meta custom fields)");
    say("Ve lead map'i sil: storage/app/kommo_leadmap.json");
    exit;
}
