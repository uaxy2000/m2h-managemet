<?php
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: text/plain; charset=utf-8');
foreach (App\Models\Pipeline::with('stages')->orderBy('sort_order')->get() as $p) {
    echo "Pipeline [{$p->id}]: {$p->name}\n";
    foreach ($p->stages->sortBy('sort_order') as $s) {
        echo "  [{$s->id}] {$s->name}\n";
    }
}
echo "\nKommo users:\n";
// Also fetch Kommo users
$token = env('KOMMO_TOKEN');
$sub   = env('KOMMO_SUBDOMAIN');
$ch = curl_init("https://{$sub}.kommo.com/api/v4/users");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer {$token}"]]);
$r = json_decode(curl_exec($ch), true);
curl_close($ch);
foreach ($r['_embedded']['users'] ?? [] as $u) {
    echo "  [{$u['id']}] {$u['name']} ({$u['email']})\n";
}
// Also get total lead count
$ch2 = curl_init("https://{$sub}.kommo.com/api/v4/leads?limit=250&page=1");
curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer {$token}"]]);
$r2 = json_decode(curl_exec($ch2), true);
curl_close($ch2);
$count = count($r2['_embedded']['leads'] ?? []);
$hasNext = isset($r2['_links']['next']);
echo "\nKommo lead sayısı (sayfa 1, limit 250): {$count} lead" . ($hasNext ? " — devam var" : " — hepsi bu") . "\n";
echo "(Bu dosyayı sil: public/_stages.php)\n";
