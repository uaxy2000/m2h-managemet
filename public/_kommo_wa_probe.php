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

// 1. Talks endpoint
echo "--- GET /api/v4/talks (limit=3) ---\n";
$r = kommo($base, $token, '/talks', ['limit' => 3]);
echo "HTTP {$r['code']}\n";
echo json_encode($r['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Tek bir talk varsa mesajlarını dene
$talks = $r['body']['_embedded']['talks'] ?? $r['body']['_embedded']['chats'] ?? [];
if (!empty($talks)) {
    $firstId = $talks[0]['id'] ?? null;
    if ($firstId) {
        echo "--- GET /api/v4/talks/{$firstId}/messages (limit=5) ---\n";
        $r2 = kommo($base, $token, "/talks/{$firstId}/messages", ['limit' => 5]);
        echo "HTTP {$r2['code']}\n";
        echo json_encode($r2['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
}

// 3. Chats endpoint (alternatif)
echo "--- GET /api/v4/chats (limit=3) ---\n";
$r3 = kommo($base, $token, '/chats', ['limit' => 3]);
echo "HTTP {$r3['code']}\n";
echo json_encode($r3['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== BITTI ===\n";
