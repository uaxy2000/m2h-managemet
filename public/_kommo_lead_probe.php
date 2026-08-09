<?php
// İlk 3 lead'in tüm custom field'larını ve contact bilgilerini göster
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: text/plain; charset=utf-8');

$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$base  = "https://{$sub}.kommo.com/api/v4";

function kommo(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 30]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $code === 200 ? (json_decode($body, true) ?? []) : ['_error' => "HTTP {$code}"];
}

// İlk 3 lead
$leads = kommo("{$base}/leads?limit=3&with=contacts,custom_fields_values", $token);
foreach ($leads['_embedded']['leads'] ?? [] as $lead) {
    echo "=== Lead [{$lead['id']}]: {$lead['name']} ===\n";
    echo "Lead custom fields:\n";
    foreach ($lead['custom_fields_values'] ?? [] as $cf) {
        $vals = implode(', ', array_column($cf['values'], 'value'));
        echo "  [{$cf['field_id']}] {$cf['field_name']}: {$vals}\n";
    }
    // Contact
    $cId = $lead['_embedded']['contacts'][0]['id'] ?? null;
    if ($cId) {
        $contact = kommo("{$base}/contacts/{$cId}?with=custom_fields_values", $token);
        echo "Contact [{$cId}]: {$contact['name']}\n";
        echo "Contact custom fields:\n";
        foreach ($contact['custom_fields_values'] ?? [] as $cf) {
            $vals = implode(', ', array_column($cf['values'], 'value'));
            echo "  [{$cf['field_id']}] {$cf['field_name']}: {$vals}\n";
        }
    }
    echo "\n";
}
echo "(Sil: public/_kommo_lead_probe.php)\n";
