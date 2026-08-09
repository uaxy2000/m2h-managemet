<?php
/**
 * Sunday Step 3: Meta form cevaplarından custom field doldurma
 * Meta Graph API'den tüm form lead'lerini çeker, Meta lead ID ile eşleştirip
 * custom field değerlerini lead_custom_values tablosuna yazar.
 *
 * Önce _sunday_4_meta_lead_id.php çalıştırılmalıdır.
 *
 * URL: https://management.m2h.ge/_sunday_3_meta_cf.php
 */

set_time_limit(300);
ini_set('max_execution_time', 300);

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

header('Content-Type: text/plain; charset=utf-8');

function metaGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['error' => "HTTP {$code}: " . substr($body ?? '', 0, 200)];
    return json_decode($body, true) ?? [];
}

function normalizeKey(string $key): string {
    return str_replace(' ', '_', mb_strtolower(str_replace(['İ', 'I'], 'i', trim($key)), 'UTF-8'));
}

$debug = isset($_GET['debug']);

say("=== META CUSTOM FIELDS IMPORT ===");
say("Başlangıç: " . date('Y-m-d H:i:s'));
say("");

// ── Soru mapping'leri yükle ───────────────────────────────────────────────
$questionMappings = DB::table('meta_question_mappings')
    ->join('custom_fields', 'custom_fields.id', '=', 'meta_question_mappings.custom_field_id')
    ->select('meta_question_mappings.meta_question_key', 'meta_question_mappings.custom_field_id', 'custom_fields.type', 'custom_fields.key as field_key')
    ->get()
    ->keyBy(fn($m) => normalizeKey($m->meta_question_key));

say("Meta soru mapping'leri: " . $questionMappings->count());

// Options (meta_aliases ile)
$fieldOptions = DB::table('custom_field_options')
    ->select('id', 'custom_field_id', 'value', 'meta_aliases')
    ->whereNotNull('meta_aliases')
    ->get()
    ->groupBy('custom_field_id');

// ── Sistemdeki leadleri Meta lead ID'ye göre index'le ────────────────────
$leadsByMetaId = DB::table('leads')->whereNotNull('meta_lead_id')->pluck('id', 'meta_lead_id')->toArray();
say("Meta lead ID'si olan lead sayısı: " . count($leadsByMetaId));

// ── Meta form mapping'leri (page token dahil) ─────────────────────────────
$formMappings = DB::table('meta_form_mappings')
    ->join('meta_pages', 'meta_pages.id', '=', 'meta_form_mappings.meta_page_id')
    ->select('meta_form_mappings.form_id', 'meta_form_mappings.form_name', 'meta_pages.access_token')
    ->whereNotNull('meta_form_mappings.form_id')
    ->get();

say("Meta form sayısı: " . $formMappings->count());
say("");

$stats = ['leads_fetched' => 0, 'leads_matched' => 0, 'fields_set' => 0, 'unmatched' => 0, 'errors' => 0];

// ── Her form için lead'leri çek ───────────────────────────────────────────
foreach ($formMappings as $form) {
    say("Form: {$form->form_name} ({$form->form_id})");

    $nextUrl = "https://graph.facebook.com/v20.0/{$form->form_id}/leads"
        . "?access_token={$form->access_token}"
        . "&fields=field_data,created_time&limit=500";

    $pageNum = 0;
    do {
        $pageNum++;
        $resp = metaGet($nextUrl);
        if (isset($resp['error'])) {
            say("  HATA: " . (is_array($resp['error']) ? ($resp['error']['message'] ?? json_encode($resp['error'])) : $resp['error']));
            $stats['errors']++;
            break;
        }

        $metaLeads = $resp['data'] ?? [];
        $stats['leads_fetched'] += count($metaLeads);
        say("  Sayfa {$pageNum}: " . count($metaLeads) . " lead");

        if ($debug && $pageNum === 1) {
            say("\n  [DEBUG] İlk 3 Meta lead:");
            foreach (array_slice($metaLeads, 0, 3) as $i => $ml) {
                say("  Lead " . ($i + 1) . " (Meta ID: {$ml['id']}):");
                foreach ($ml['field_data'] ?? [] as $item) {
                    say("    [{$item['name']}] = " . ($item['values'][0] ?? '(boş)'));
                }
                $matched = isset($leadsByMetaId[$ml['id']]) ? 'EŞLEŞTİ: ' . $leadsByMetaId[$ml['id']] : 'eşleşmedi';
                say("    → CRM: {$matched}");
            }
            say("\n  [DEBUG] CRM'deki ilk 5 Meta lead ID:");
            foreach (array_slice(array_keys($leadsByMetaId), 0, 5) as $mid) {
                say("    " . $mid);
            }
            say("");
        }

        foreach ($metaLeads as $ml) {
            // Alan değerlerini parse et
            $fields = [];
            foreach ($ml['field_data'] ?? [] as $item) {
                $fields[$item['name']] = $item['values'][0] ?? null;
            }

            // Meta lead ID ile eşleştir
            $metaLeadId = (string) $ml['id'];
            $ourLeadId  = $leadsByMetaId[$metaLeadId] ?? null;

            if (!$ourLeadId) { $stats['unmatched']++; continue; }
            $stats['leads_matched']++;

            // Her alan için custom field eşleştir
            foreach ($fields as $rawKey => $rawValue) {
                if ($rawValue === null || $rawValue === '') continue;

                $normKey    = normalizeKey($rawKey);
                $mapping    = $questionMappings[$normKey] ?? null;
                if (!$mapping) continue;

                $fieldId   = $mapping->custom_field_id;
                $fieldType = $mapping->type;

                if (in_array($fieldType, ['select', 'multi_select'])) {
                    $normValue = normalizeKey($rawValue);
                    $matchedValue = null;

                    foreach ($fieldOptions[$fieldId] ?? [] as $opt) {
                        $aliases = json_decode($opt->meta_aliases ?? '[]', true) ?? [];
                        foreach ($aliases as $alias) {
                            if (normalizeKey($alias) === $normValue) {
                                $matchedValue = $fieldType === 'multi_select'
                                    ? json_encode([$opt->value])
                                    : $opt->value;
                                break 2;
                            }
                        }
                    }

                    if ($matchedValue === null) continue;
                    $storedValue = $matchedValue;
                } else {
                    $storedValue = $rawValue;
                }

                try {
                    $existing = DB::table('lead_custom_values')
                        ->where('lead_id', $ourLeadId)
                        ->where('custom_field_id', $fieldId)
                        ->first();

                    if ($existing) {
                        DB::table('lead_custom_values')
                            ->where('id', $existing->id)
                            ->update(['value' => $storedValue, 'updated_at' => now()]);
                    } else {
                        DB::table('lead_custom_values')->insert([
                            'id'              => (string) Str::uuid(),
                            'lead_id'         => $ourLeadId,
                            'custom_field_id' => $fieldId,
                            'value'           => $storedValue,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                    $stats['fields_set']++;
                } catch (\Throwable $e) {
                    say("  CF HATA [{$mapping->field_key}]: " . $e->getMessage());
                    $stats['errors']++;
                }
            }
        }

        $nextUrl = $resp['paging']['next'] ?? null;
        usleep(200000);
    } while ($nextUrl);

    say("");
}

say("=== TAMAMLANDI ===");
say("Meta lead çekildi  : {$stats['leads_fetched']}");
say("CRM'de eşleşti     : {$stats['leads_matched']}");
say("Eşleşmedi (meta_id) : {$stats['unmatched']}");
say("Custom field yazıldı: {$stats['fields_set']}");
say("Hata               : {$stats['errors']}");
say("Bitiş              : " . date('Y-m-d H:i:s'));
say("\nBu dosyayı sil: public/_sunday_3_meta_cf.php");

function say(string $msg): void { echo $msg . "\n"; }
