<?php
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: text/plain; charset=utf-8');

$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$base  = "https://{$sub}.kommo.com/api/v4";

$ch = curl_init("{$base}/leads/pipelines");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"]]);
$body = curl_exec($ch);
curl_close($ch);

$data = json_decode($body, true);
foreach ($data['_embedded']['pipelines'] ?? [] as $p) {
    echo "Pipeline [{$p['id']}]: {$p['name']}\n";
    foreach ($p['_embedded']['statuses'] ?? [] as $s) {
        echo "  Stage [{$s['id']}]: {$s['name']}\n";
    }
    echo "\n";
}
echo "(Sil: public/_kommo_stages.php)\n";
