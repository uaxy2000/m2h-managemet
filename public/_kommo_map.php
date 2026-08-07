<?php
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: text/plain; charset=utf-8');

use Illuminate\Support\Facades\DB;

$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$base  = "https://{$sub}.kommo.com/api/v4";

function kommo(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer {$token}"]]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $code === 200 ? (json_decode($body, true) ?? []) : ['_error'=>"HTTP {$code}"];
}

// 1. Bizim pipeline/stage'ler
echo "=== BİZİM PIPELINE/STAGE'LER ===\n";
foreach (App\Models\Pipeline::with('stages')->orderBy('sort_order')->get() as $p) {
    echo "Pipeline [{$p->id}]: {$p->name}\n";
    foreach ($p->stages->sortBy('sort_order') as $s) {
        echo "  [{$s->id}] {$s->name}\n";
    }
}

// 2. Kommo pipeline/stage'ler
echo "\n=== KOMMO PIPELINE/STAGE'LER ===\n";
$kp = kommo("{$base}/leads/pipelines", $token);
foreach ($kp['_embedded']['pipelines'] ?? [] as $p) {
    echo "Pipeline [{$p['id']}]: {$p['name']}\n";
    foreach ($p['_embedded']['statuses'] ?? [] as $s) {
        echo "  [{$s['id']}] {$s['name']}\n";
    }
}

// 3. Kommo contact custom fields (form cevapları burada mı?)
echo "\n=== KOMMO CONTACT CUSTOM FIELDS ===\n";
$ccf = kommo("{$base}/contacts/custom_fields", $token);
foreach ($ccf['_embedded']['custom_fields'] ?? [] as $cf) {
    echo "  [{$cf['id']}] {$cf['name']} (tip: {$cf['type']})\n";
    foreach ($cf['enums'] ?? [] as $e) {
        echo "    - {$e['value']}: {$e['value']}\n";
    }
}

// 4. Örnek contact custom field değerleri
echo "\n=== ÖRNEK CONTACT (ilk lead'in contact'ı) ===\n";
$leads = kommo("{$base}/leads?limit=1", $token);
$firstLead = $leads['_embedded']['leads'][0] ?? null;
if ($firstLead) {
    $contacts = kommo("{$base}/leads/{$firstLead['id']}?with=contacts", $token);
    $cid = $contacts['_embedded']['contacts'][0]['id'] ?? null;
    if ($cid) {
        $contact = kommo("{$base}/contacts/{$cid}", $token);
        echo "Ad: {$contact['name']}\n";
        foreach ($contact['custom_fields_values'] ?? [] as $cf) {
            $vals = implode(', ', array_column($cf['values'], 'value'));
            echo "  [{$cf['field_id']}] {$cf['field_name']}: {$vals}\n";
        }
    }
}

// 5. Toplam lead sayısı (tüm sayfaları say)
echo "\n=== TOPLAM LEAD SAYISI ===\n";
$page = 1; $total = 0;
do {
    $r = kommo("{$base}/leads?limit=250&page={$page}", $token);
    $batch = count($r['_embedded']['leads'] ?? []);
    $total += $batch;
    $hasNext = isset($r['_links']['next']);
    $page++;
} while ($hasNext && $page <= 20);
echo "Toplam: {$total} lead\n";

echo "\n(Bu dosyayı sil: public/_kommo_map.php)\n";
