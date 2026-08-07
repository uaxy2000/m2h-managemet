<?php
/**
 * Kommo API keşif scripti — veri yapısını görmek için.
 * Kullanım: https://domain.com/_kommo_probe.php
 * Çalıştırdıktan sonra sil.
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$subdomain = env('KOMMO_SUBDOMAIN');
$token     = env('KOMMO_TOKEN');
$base      = "https://{$subdomain}.kommo.com/api/v4";

if (!$subdomain || !$token) {
    echo "HATA: KOMMO_SUBDOMAIN veya KOMMO_TOKEN .env'de bulunamadı.\n";
    exit(1);
}

function kommo(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", "Content-Type: application/json"],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        return ['_error' => "HTTP {$code}", '_body' => $body];
    }
    return json_decode($body, true) ?? ['_error' => 'JSON parse hatası'];
}

echo "=== KOMMO API PROBE ===\n";
echo "Subdomain: {$subdomain}\n";
echo "Base URL: {$base}\n\n";

// 1. Pipelines & stages
echo "--- 1. PIPELINES & STAGES ---\n";
$pipelines = kommo("{$base}/leads/pipelines", $token);
if (isset($pipelines['_embedded']['pipelines'])) {
    foreach ($pipelines['_embedded']['pipelines'] as $p) {
        echo "Pipeline [{$p['id']}]: {$p['name']}\n";
        foreach ($p['_embedded']['statuses'] ?? [] as $s) {
            echo "  Stage [{$s['id']}]: {$s['name']} (sort: {$s['sort']})\n";
        }
    }
} else {
    echo json_encode($pipelines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 2. Lead sayısı
echo "\n--- 2. TOPLAM LEAD SAYISI ---\n";
$leadsInfo = kommo("{$base}/leads?limit=1", $token);
$total = $leadsInfo['_page_count'] ?? $leadsInfo['_total_items'] ?? '?';
echo "Toplam lead (yaklaşık): {$total}\n";
// page_count ile limit=1 → her page 1 lead → page_count = toplam
// Alternatif: _total_items
echo "Yanıt meta: " . json_encode($leadsInfo['_page'] ?? [], JSON_PRETTY_PRINT) . "\n";

// 3. Örnek 2 lead (tüm field'larla)
echo "\n--- 3. ÖRNEK 3 LEAD (tüm alanlar) ---\n";
$leads = kommo("{$base}/leads?with=contacts,tags,custom_fields_values&limit=3", $token);
if (isset($leads['_embedded']['leads'])) {
    foreach ($leads['_embedded']['leads'] as $lead) {
        echo "\nLead ID: {$lead['id']}\n";
        echo "  Ad: " . ($lead['name'] ?? '-') . "\n";
        echo "  Pipeline ID: " . ($lead['pipeline_id'] ?? '-') . "\n";
        echo "  Stage ID: " . ($lead['status_id'] ?? '-') . "\n";
        echo "  Sorumlu: " . ($lead['responsible_user_id'] ?? '-') . "\n";
        echo "  Oluşturma: " . ($lead['created_at'] ? date('Y-m-d H:i', $lead['created_at']) : '-') . "\n";
        echo "  Tags: " . implode(', ', array_column($lead['_embedded']['tags'] ?? [], 'name')) . "\n";
        echo "  Custom Fields:\n";
        foreach ($lead['custom_fields_values'] ?? [] as $cf) {
            $vals = implode(', ', array_column($cf['values'], 'value'));
            echo "    [{$cf['field_id']}] {$cf['field_name']}: {$vals}\n";
        }
        echo "  Contacts:\n";
        foreach ($lead['_embedded']['contacts'] ?? [] as $c) {
            echo "    Contact ID: {$c['id']} (is_main: " . ($c['is_main'] ? 'evet' : 'hayır') . ")\n";
        }
    }
} else {
    echo json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 4. Örnek contact detayı (telefon/email için)
echo "\n--- 4. ÖRNEK CONTACT DETAYI ---\n";
if (!empty($leads['_embedded']['leads'][0]['_embedded']['contacts'][0]['id'])) {
    $cid     = $leads['_embedded']['leads'][0]['_embedded']['contacts'][0]['id'];
    $contact = kommo("{$base}/contacts/{$cid}", $token);
    echo "Contact ID: {$cid}\n";
    echo "Ad: " . ($contact['name'] ?? '-') . "\n";
    foreach ($contact['custom_fields_values'] ?? [] as $cf) {
        $vals = implode(', ', array_column($cf['values'], 'value'));
        echo "  [{$cf['field_name']}]: {$vals}\n";
    }
} else {
    echo "Contact bulunamadı.\n";
}

// 5. Notlar
echo "\n--- 5. ÖRNEK NOTLAR (ilk 5) ---\n";
$notes = kommo("{$base}/leads/notes?limit=5", $token);
if (isset($notes['_embedded']['notes'])) {
    foreach ($notes['_embedded']['notes'] as $n) {
        echo "  Note ID: {$n['id']} | Lead: {$n['entity_id']} | Tip: {$n['note_type']}\n";
        $text = $n['params']['text'] ?? $n['params']['service'] ?? json_encode($n['params'] ?? []);
        echo "  İçerik: " . mb_substr($text, 0, 120) . "\n\n";
    }
} else {
    echo json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 6. Custom field tanımları
echo "\n--- 6. LEAD CUSTOM FIELD TANIMLARI ---\n";
$cfields = kommo("{$base}/leads/custom_fields", $token);
if (isset($cfields['_embedded']['custom_fields'])) {
    foreach ($cfields['_embedded']['custom_fields'] as $cf) {
        echo "  [{$cf['id']}] {$cf['name']} (tip: {$cf['type']})\n";
    }
} else {
    echo json_encode($cfields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== PROBE TAMAMLANDI — bu dosyayı sil: public/_kommo_probe.php ===\n";
