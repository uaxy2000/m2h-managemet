<?php
/**
 * Kommo → M2H CRM Migration
 * Imports leads, contacts, tags, notes.
 * URL: https://management.m2h.ge/_kommo_import.php
 * Delete after use: git rm public/_kommo_import.php
 */

set_time_limit(600);
ini_set('max_execution_time', 600);

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

header('Content-Type: text/plain; charset=utf-8');

$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$base  = "https://{$sub}.kommo.com/api/v4";

// ── Stage mapping: [kommo_pipeline_id][kommo_status_id] → [our_pipeline_id, our_stage_id] ──
$MAIN = 'a2231030-9124-4a1d-a647-84cf4fb7f277';
$COLD = 'a25182a1-4b78-4249-8c46-17ee529c7fb6';
$stageMap = [
    13154123 => [ // MainLine
        101431363 => [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'], // Gelen müşteriler → LEAD RECEIVED
        101792603 => [$MAIN, 'a223394e-b26a-4d3a-b95b-e98014f0d8db'], // NO CONTACT YET → NO CONTACT
        101431367 => [$MAIN, 'a25fb42f-ea73-46ac-b457-7e7816537170'], // FIRST CONTACT
        102333639 => [$MAIN, 'a2663250-d9b6-4bf4-abc8-5cacf738486e'], // DISCOVERY CALL
        104144407 => [$MAIN, 'a2663266-e3b6-42cd-b998-a479287c5256'], // LEAD REGISTERED
        101431371 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'], // MEETING SET → MEETING 1
        101431375 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'], // MEETING 1 DONE → MEETING 1
        101431379 => [$MAIN, 'a2719f9f-9895-497a-8bce-12d9ab5e38a7'], // CONTRACT NEGOTIATIONS
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'], // Closed-won → WON
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'], // Closed-lost → LOST
    ],
    13270595 => [ // ColdLead
        102332047 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Incoming leads → Cold Start
        102332051 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // COLD START
        102332055 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Offer made
        102332059 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'], // Negotiation
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'], // Won → Main Line WON
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'], // Lost → Main Line LOST
    ],
];

// ── Helpers ──────────────────────────────────────────────────────────────
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
    if ($code === 204) return [];
    if ($code !== 200) return ['_error' => "HTTP {$code}: " . substr($body, 0, 100)];
    return json_decode($body, true) ?? [];
}

function say(string $msg): void { echo $msg . "\n"; }

function getOrCreateTag(string $name, string $groupId, array &$cache): string {
    $key = mb_strtolower(trim($name));
    if (isset($cache[$key])) return $cache[$key];
    $existing = DB::table('tags')->whereRaw('LOWER(name) = ?', [$key])->value('id');
    if ($existing) { $cache[$key] = $existing; return $existing; }
    $id = (string) Str::uuid();
    DB::table('tags')->insert([
        'id' => $id, 'name' => trim($name), 'color' => '#64748b',
        'tag_group_id' => $groupId, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cache[$key] = $id;
    return $id;
}

// ════════════════════════════════════════════════════════
say("=== KOMMO → M2H IMPORT ===");
say("Başlangıç: " . date('Y-m-d H:i:s'));
say("");

// ── Phase 1: Kurulum ─────────────────────────────────────────────────────
say("--- Phase 1: Kurulum ---");

$internalCompanyId = DB::table('companies')->where('type', 'internal')->orderBy('created_at')->value('id');
say("Internal company: {$internalCompanyId}");

$ourUsers = DB::table('users')->select('id', 'email')->get()->keyBy('email');
$userMap  = [
    14808127 => $ourUsers['info@m2h.ge']?->id ?? null,
    15069907 => $ourUsers['can@m2h.ge']?->id  ?? null,
];
foreach ($userMap as $kId => $oId) {
    say("  Kommo user {$kId} → " . ($oId ? $oId : 'BULUNAMADI'));
}
$defaultUser = $userMap[14808127];
if (!$defaultUser) { say("HATA: Default user bulunamadı!"); exit(1); }

// Kommo Tags grubu
$kommoGroup = DB::table('tag_groups')->where('name', 'Kommo Tags')->first();
if (!$kommoGroup) {
    $kommoGroupId = (string) Str::uuid();
    DB::table('tag_groups')->insert(['id' => $kommoGroupId, 'name' => 'Kommo Tags', 'created_at' => now(), 'updated_at' => now()]);
    say("'Kommo Tags' grubu oluşturuldu.");
} else {
    $kommoGroupId = $kommoGroup->id;
    say("'Kommo Tags' grubu mevcut.");
}

$tagCache      = DB::table('tags')->select('id', 'name')->get()->keyBy(fn($t) => mb_strtolower($t->name))->map(fn($t) => $t->id)->toArray();
$existingPhones = DB::table('leads')->whereNotNull('phone')->pluck('id', 'phone')->toArray();

$stats    = ['created' => 0, 'skipped' => 0, 'notes' => 0, 'tags' => 0, 'errors' => 0];
$leadMap  = []; // kommo lead ID → our lead UUID

say("");

// ── Phase 2: Lead'ler ────────────────────────────────────────────────────
say("--- Phase 2: Lead'ler ---");

$page = 1;
do {
    say("\n[Sayfa {$page}] Yükleniyor...");
    $resp = kommoGet("{$base}/leads?with=contacts,tags&limit=250&page={$page}", $token);
    usleep(200000);

    if (isset($resp['_error'])) {
        say("HATA: " . $resp['_error']);
        $stats['errors']++;
        break;
    }

    $kLeads  = $resp['_embedded']['leads'] ?? [];
    $hasNext = isset($resp['_links']['next']);
    say(count($kLeads) . " lead alındı.");

    // Batch contact fetch (20'li gruplar)
    $contactIdToLeadId = [];
    foreach ($kLeads as $kl) {
        foreach ($kl['_embedded']['contacts'] ?? [] as $c) {
            if ($c['is_main'] ?? false) { $contactIdToLeadId[$c['id']] = $kl['id']; break; }
        }
        if (!isset($contactIdToLeadId) || !array_key_exists($kl['id'], array_flip($contactIdToLeadId))) {
            $first = $kl['_embedded']['contacts'][0]['id'] ?? null;
            if ($first) $contactIdToLeadId[$first] = $kl['id'];
        }
    }

    $contactDetails = [];
    foreach (array_chunk(array_keys($contactIdToLeadId), 20) as $cChunk) {
        $qs   = implode('&', array_map(fn($id) => "filter[id][]={$id}", $cChunk));
        $cRes = kommoGet("{$base}/contacts?{$qs}&with=custom_fields_values", $token);
        usleep(150000);
        foreach ($cRes['_embedded']['contacts'] ?? [] as $c) {
            $phone = $email = null;
            foreach ($c['custom_fields_values'] ?? [] as $cf) {
                $code = $cf['field_code'] ?? '';
                if ($code === 'PHONE') $phone = $cf['values'][0]['value'] ?? null;
                if ($code === 'EMAIL') $email = $cf['values'][0]['value'] ?? null;
            }
            $contactDetails[$c['id']] = ['name' => $c['name'] ?? null, 'phone' => $phone, 'email' => $email];
        }
    }

    // Import each lead
    foreach ($kLeads as $kl) {
        $kId = $kl['id'];

        // Find contact
        $cId      = null;
        foreach ($kl['_embedded']['contacts'] ?? [] as $c) {
            if ($c['is_main'] ?? false) { $cId = $c['id']; break; }
        }
        if (!$cId) $cId = $kl['_embedded']['contacts'][0]['id'] ?? null;

        $contact   = $cId ? ($contactDetails[$cId] ?? []) : [];
        $fullName  = trim($contact['name'] ?? "Kommo #{$kId}");
        $phone     = isset($contact['phone']) ? preg_replace('/\s+/', '', $contact['phone']) : null;
        $email     = $contact['email'] ?? null;

        // Name split
        $parts     = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?: 'Unknown';
        $lastName  = $parts[1] ?? null;

        // Stage
        $kPipeline = $kl['pipeline_id'];
        $kStage    = $kl['status_id'];
        $mapped    = $stageMap[$kPipeline][$kStage] ?? [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'];
        [$ourPipeline, $ourStage] = $mapped;

        // User
        $ourUser = $userMap[$kl['responsible_user_id'] ?? 0] ?? $defaultUser;

        // Source
        $tagNames = array_column($kl['_embedded']['tags'] ?? [], 'name');
        $source   = in_array('Meta Campaign', $tagNames) ? 'meta_ad' : 'manual';

        // Deduplicate by phone
        if ($phone && isset($existingPhones[$phone])) {
            $leadMap[$kId] = $existingPhones[$phone];
            $stats['skipped']++;
            continue;
        }

        $ourLeadId = (string) Str::uuid();
        $createdAt = $kl['created_at'] ? date('Y-m-d H:i:s', $kl['created_at']) : now()->toDateTimeString();

        DB::table('leads')->insert([
            'id'          => $ourLeadId,
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'phone'       => $phone,
            'email'       => $email,
            'source'      => $source,
            'pipeline_id' => $ourPipeline,
            'stage_id'    => $ourStage,
            'assigned_to' => $ourUser,
            'company_id'  => $internalCompanyId,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);

        if ($phone) $existingPhones[$phone] = $ourLeadId;
        $leadMap[$kId] = $ourLeadId;
        $stats['created']++;

        // Tags
        foreach ($tagNames as $tn) {
            if (!trim($tn)) continue;
            $tagId = getOrCreateTag($tn, $kommoGroupId, $tagCache);
            DB::table('lead_tags')->insertOrIgnore(['lead_id' => $ourLeadId, 'tag_id' => $tagId]);
            $stats['tags']++;
        }
    }

    $page++;
} while ($hasNext && $page <= 20);

say("\nLead: {$stats['created']} oluşturuldu, {$stats['skipped']} atlandı.");

// ── Phase 3: Notlar ──────────────────────────────────────────────────────
say("\n--- Phase 3: Notlar ---");

$kommoLeadIds = array_keys($leadMap);

foreach (array_chunk($kommoLeadIds, 20) as $chunk) {
    $qs   = implode('&', array_map(fn($id) => "filter[entity_id][]={$id}", $chunk));
    $nRes = kommoGet("{$base}/leads/notes?{$qs}&limit=250", $token);
    usleep(200000);

    if (isset($nRes['_error'])) { $stats['errors']++; continue; }

    foreach ($nRes['_embedded']['notes'] ?? [] as $note) {
        if ($note['note_type'] !== 'common') continue;

        $text = trim($note['params']['text'] ?? '');
        if ($text === '') continue;

        $ourLeadId = $leadMap[$note['entity_id']] ?? null;
        if (!$ourLeadId) continue;

        $noteUser = $userMap[$note['created_by'] ?? 0] ?? $defaultUser;
        $noteAt   = $note['created_at'] ? date('Y-m-d H:i:s', $note['created_at']) : now()->toDateTimeString();

        DB::table('notes')->insert([
            'id'         => (string) Str::uuid(),
            'lead_id'    => $ourLeadId,
            'created_by' => $noteUser,
            'content'    => $text,
            'visibility' => 'internal',
            'created_at' => $noteAt,
        ]);
        $stats['notes']++;
    }
}

say("Not: {$stats['notes']} oluşturuldu.");

// ── Özet ─────────────────────────────────────────────────────────────────
say("\n=== ÖZET ===");
say("Lead oluşturuldu : {$stats['created']}");
say("Lead atlandı     : {$stats['skipped']} (telefon çakışması)");
say("Tag işlendi      : {$stats['tags']}");
say("Not oluşturuldu  : {$stats['notes']}");
say("Hata             : {$stats['errors']}");
say("Bitiş            : " . date('Y-m-d H:i:s'));
say("\nBu dosyayı sil: public/_kommo_import.php");
