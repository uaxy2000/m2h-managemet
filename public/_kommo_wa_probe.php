<?php
/**
 * Kommo API - WhatsApp/Talks endpoint probe
 * URL: https://management.m2h.ge/_kommo_wa_probe.php?token=TOKEN&domain=DOMAIN
 *
 * DOMAIN: örn. m2h.kommo.com (sadece subdomain kısmı)
 * TOKEN : Kommo long-lived token
 *
 * Calistirdiktan sonra sil.
 */

header('Content-Type: text/plain; charset=utf-8');

$token  = $_GET['token']  ?? '';
$domain = $_GET['domain'] ?? 'm2h.kommo.com';

if (!$token) {
    echo "Kullanim: ?token=TOKEN&domain=m2h.kommo.com\n";
    echo "Token chat'e yazma, URL'e ekle.\n";
    exit(1);
}

$base = "https://{$domain}/api/v4";

function kommo(string $base, string $token, string $path, array $params = []): array
{
    $url = $base . $path;
    if ($params) $url .= '?' . http_build_query($params);

    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);

    $raw  = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
        $code = (int) ($m[1] ?? 0);
    }

    return ['code' => $code, 'body' => json_decode($raw ?: '{}', true) ?? [], 'raw' => $raw];
}

echo "=== KOMMO WA PROBE ===\n";
echo "Domain : {$domain}\n";
echo "Base   : {$base}\n\n";

// 1. Talks - toplam sayı ve tüm origin'ler
echo "--- GET /api/v4/talks (limit=50, sayfa 1) ---\n";
$r = kommo($base, $token, '/talks', ['limit' => 50, 'page' => 1]);
echo "HTTP {$r['code']}\n";
$allTalks = $r['body']['_embedded']['talks'] ?? [];
$total    = $r['body']['_total_items'] ?? 0;
echo "Toplam talk: {$total}\n";

// Origin dağılımı
$origins = array_count_values(array_column($allTalks, 'origin'));
echo "Origin dağılımı: " . json_encode($origins) . "\n\n";

// 2. WABA talk'larını listele
$wabaTalks = array_filter($allTalks, fn($t) => ($t['origin'] ?? '') === 'waba');
echo "WABA talk sayısı: " . count($wabaTalks) . "\n";
foreach ($wabaTalks as $t) {
    echo "  talk_id={$t['talk_id']} contact_id={$t['contact_id']} kommo_lead={$t['entity_id']} chat_id={$t['chat_id']}\n";
}
echo "\n";

// 3. İlk WABA talk'ın mesajlarını çek (talk_id kullan!)
$firstWaba = reset($wabaTalks);
if ($firstWaba) {
    $tid = $firstWaba['talk_id'];
    echo "--- GET /api/v4/talks/{$tid}/messages (limit=10) ---\n";
    $r2 = kommo($base, $token, "/talks/{$tid}/messages", ['limit' => 10]);
    echo "HTTP {$r2['code']}\n";
    echo json_encode($r2['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
} else {
    // WABA yok, ilk talk'ın mesajlarına bak
    $first = reset($allTalks);
    if ($first) {
        $tid = $first['talk_id'];
        echo "--- GET /api/v4/talks/{$tid}/messages (limit=5, WABA degil) ---\n";
        $r2 = kommo($base, $token, "/talks/{$tid}/messages", ['limit' => 5]);
        echo "HTTP {$r2['code']}\n";
        echo json_encode($r2['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
}

echo "=== BITTI ===\n";
