<?php
/**
 * Kommo → M2H CRM Migration (sayfa sayfa çalışır)
 *
 * Adımlar:
 *   ?page=1        → ilk 250 lead
 *   ?page=2        → sonraki 250 lead
 *   ?page=3/4      → devamı
 *   ?step=notes    → notları import et
 *
 * Lead map storage/app/kommo_leadmap.json dosyasına kaydedilir.
 * Delete after use: git rm public/_kommo_import.php
 */

set_time_limit(120);
ini_set('max_execution_time', 120);

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

header('Content-Type: text/plain; charset=utf-8');

$token   = env('KOMMO_TOKEN');
$sub     = env('KOMMO_SUBDOMAIN');
$base    = "https://{$sub}.kommo.com/api/v4";
$mapFile = storage_path('app/kommo_leadmap.json');

// ── Stage mapping ────────────────────────────────────────────────────────
$MAIN = 'a2231030-9124-4a1d-a647-84cf4fb7f277';
$COLD = 'a25182a1-4b78-4249-8c46-17ee529c7fb6';
$stageMap = [
    13154123 => [
        101431363 => [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'],
        101792603 => [$MAIN, 'a223394e-b26a-4d3a-b95b-e98014f0d8db'],
        101431367 => [$MAIN, 'a25fb42f-ea73-46ac-b457-7e7816537170'],
        102333639 => [$MAIN, 'a2663250-d9b6-4bf4-abc8-5cacf738486e'],
        104144407 => [$MAIN, 'a2663266-e3b6-42cd-b998-a479287c5256'],
        101431371 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'],
        101431375 => [$MAIN, 'a2719f21-6735-413d-8dae-261dc2a5f964'],
        101431379 => [$MAIN, 'a2719f9f-9895-497a-8bce-12d9ab5e38a7'],
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'],
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'],
    ],
    13270595 => [
        102332047 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'],
        102332051 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'],
        102332055 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'],
        102332059 => [$COLD, 'a271a00e-b07f-4180-8aaa-a01cab86b597'],
        142       => [$MAIN, 'a2719fc4-f25b-4f9f-806f-d01e101496c8'],
        143       => [$MAIN, 'a2719fef-6e4d-432f-a5da-c7cfdca41d5d'],
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

function loadLeadMap(string $mapFile): array {
    return file_exists($mapFile) ? (json_decode(file_get_contents($mapFile), true) ?? []) : [];
}

function saveLeadMap(array $map, string $mapFile): void {
    file_put_contents($mapFile, json_encode($map));
}

// ── Routing ──────────────────────────────────────────────────────────────
$step = $_GET['step'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : null;

if (!$page && $step !== 'notes') {
    say("Kullanım:");
    say("  ?page=1        → lead'ler sayfa 1 (250 lead)");
    say("  ?page=2        → lead'ler sayfa 2");
    say("  ?page=3        → lead'ler sayfa 3");
    say("  ?page=4        → lead'ler sayfa 4");
    say("  ?step=notes    → notları import et");
    say("");
    $existing = loadLeadMap($mapFile);
    say("Şu ana kadar map'lenen lead: " . count($existing));
    exit;
}

// ── Ortak kurulum ────────────────────────────────────────────────────────
$ourUsers    = DB::table('users')->select('id', 'email', 'name')->get()->keyBy('email');
$burakId     = $ourUsers['burak@m2h.ge']?->id
    ?? $ourUsers['info@m2h.ge']?->id
    ?? $ourUsers['bzorer@gmail.com']?->id
    ?? DB::table('users')->whereIn('role', ['super_admin', 'admin'])->orderBy('created_at')->value('id');
$defaultUser = $burakId;
$userMap     = [
    14808127 => $burakId,
    15069907 => $ourUsers['can@m2h.ge']?->id ?? null,
];

$internalCompanyId = DB::table('companies')->where('type', 'internal')->orderBy('created_at')->value('id');

// Kommo Tags grubu
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
    say("Default user: {$defaultUser}");
    say("");

    $leadMap = loadLeadMap($mapFile);
    $stats   = ['created' => 0, 'skipped' => 0, 'tags' => 0, 'errors' => 0];

    say("Kommo'dan sayfa {$page} çekiliyor...");
    $resp = kommoGet("{$base}/leads?with=contacts,tags&limit=250&page={$page}", $token);

    if (isset($resp['_error'])) {
        say("HATA: " . $resp['_error']);
        exit(1);
    }

    $kLeads = $resp['_embedded']['leads'] ?? [];
    say(count($kLeads) . " lead alındı.");

    if (empty($kLeads)) {
        say("Bu sayfada lead yok, duruluyor.");
        exit;
    }

    // Batch contact fetch
    $contactIdMap = [];
    foreach ($kLeads as $kl) {
        foreach ($kl['_embedded']['contacts'] ?? [] as $c) {
            if ($c['is_main'] ?? false) { $contactIdMap[$c['id']] = true; break; }
        }
        if (empty($contactIdMap)) {
            $first = $kl['_embedded']['contacts'][0]['id'] ?? null;
            if ($first) $contactIdMap[$first] = true;
        }
    }

    // Her lead'in main contact ID'sini al
    $leadToContactId = [];
    foreach ($kLeads as $kl) {
        $cId = null;
        foreach ($kl['_embedded']['contacts'] ?? [] as $c) {
            if ($c['is_main'] ?? false) { $cId = $c['id']; break; }
        }
        if (!$cId) $cId = $kl['_embedded']['contacts'][0]['id'] ?? null;
        $leadToContactId[$kl['id']] = $cId;
    }

    $allContactIds  = array_values(array_filter(array_unique($leadToContactId)));
    $contactDetails = [];

    say("Contact batch fetch (" . count($allContactIds) . " contact)...");
    foreach (array_chunk($allContactIds, 20) as $cChunk) {
        $qs   = implode('&', array_map(fn($id) => "filter[id][]={$id}", $cChunk));
        $cRes = kommoGet("{$base}/contacts?{$qs}&with=custom_fields_values", $token);
        usleep(200000);
        if (isset($cRes['_error'])) { say("Contact fetch HATA: " . $cRes['_error']); continue; }
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

        $kPipeline  = $kl['pipeline_id'];
        $kStage     = $kl['status_id'];
        $mapped     = $stageMap[$kPipeline][$kStage] ?? [$MAIN, 'a2231044-686e-4aa0-ae83-562cc4244266'];
        [$ourPipeline, $ourStage] = $mapped;

        $ourUser  = $userMap[$kl['responsible_user_id'] ?? 0] ?? $defaultUser;
        $tagNames = array_column($kl['_embedded']['tags'] ?? [], 'name');
        $source   = in_array('Meta Campaign', $tagNames) ? 'meta_ad' : 'manual';

        // Deduplicate by phone
        if ($phone && isset($existingPhones[$phone])) {
            $leadMap[$kId] = $existingPhones[$phone];
            $stats['skipped']++;
            continue;
        }
        // Deduplicate by kommo ID (re-run safety)
        if (isset($leadMap[$kId])) {
            $stats['skipped']++;
            continue;
        }

        $ourLeadId = (string) Str::uuid();
        $createdAt = $kl['created_at'] ? date('Y-m-d H:i:s', $kl['created_at']) : now()->toDateTimeString();

        try {
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
        } catch (\Throwable $e) {
            say("INSERT HATA [{$kId}] {$fullName}: " . $e->getMessage());
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
            } catch (\Throwable $e) {
                say("TAG HATA [{$tn}]: " . $e->getMessage());
            }
        }
    }

    saveLeadMap($leadMap, $mapFile);

    say("");
    say("=== SAYFA {$page} TAMAMLANDI ===");
    say("Oluşturuldu : {$stats['created']}");
    say("Atlandı     : {$stats['skipped']}");
    say("Tag         : {$stats['tags']}");
    say("Hata        : {$stats['errors']}");
    say("Toplam map  : " . count($leadMap));
    $hasNext = isset($resp['_links']['next']);
    say($hasNext ? "\nSonraki adım: ?page=" . ($page + 1) : "\nTüm sayfalar bitti! Sonraki: ?step=notes");
    exit;
}

// ════════════════════════════════════════════════════════
// ADIM: Notlar
// ════════════════════════════════════════════════════════
if ($step === 'notes') {
    say("=== NOTLAR ===");
    say("Başlangıç: " . date('Y-m-d H:i:s'));

    $leadMap = loadLeadMap($mapFile);
    if (empty($leadMap)) {
        say("Lead map boş! Önce ?page=1 vb. adımları çalıştır.");
        exit(1);
    }
    say("Lead map: " . count($leadMap) . " kayıt");

    $stats        = ['notes' => 0, 'errors' => 0];
    $kommoLeadIds = array_keys($leadMap);

    foreach (array_chunk($kommoLeadIds, 20) as $i => $chunk) {
        say("Chunk " . ($i + 1) . "/" . ceil(count($kommoLeadIds) / 20) . "...");
        $qs   = implode('&', array_map(fn($id) => "filter[entity_id][]={$id}", $chunk));
        $nRes = kommoGet("{$base}/leads/notes?{$qs}&limit=250", $token);
        usleep(200000);

        if (isset($nRes['_error'])) {
            say("HATA: " . $nRes['_error']);
            $stats['errors']++;
            continue;
        }

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
                    'id'         => (string) Str::uuid(),
                    'lead_id'    => $ourLeadId,
                    'created_by' => $noteUser,
                    'content'    => $text,
                    'visibility' => 'internal',
                    'created_at' => $noteAt,
                ]);
                $stats['notes']++;
            } catch (\Throwable $e) {
                say("NOT HATA: " . $e->getMessage());
                $stats['errors']++;
            }
        }
    }

    say("");
    say("=== NOTLAR TAMAMLANDI ===");
    say("Not oluşturuldu : {$stats['notes']}");
    say("Hata            : {$stats['errors']}");
    say("Bitiş           : " . date('Y-m-d H:i:s'));
    say("\nArtık dosyayı silebilirsin: public/_kommo_import.php");
    say("Ve lead map'i sil: storage/app/kommo_leadmap.json");
    exit;
}
