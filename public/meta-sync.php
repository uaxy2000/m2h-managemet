<?php
// One-time use — DELETE after running
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (($_GET['key'] ?? '') !== 'sync2026') {
    http_response_code(403);
    die('Forbidden');
}

echo "<pre>\n";

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "Token configured: " . (config('services.meta_ads.access_token') ? 'YES (' . strlen(config('services.meta_ads.access_token')) . ' chars)' : 'NO') . "\n";
    echo "Account ID: " . config('services.meta_ads.account_id') . "\n\n";

    foreach (['yesterday', 'today'] as $date) {
        echo "--- Syncing: {$date} ---\n";
        $exit = Artisan::call('meta:sync-insights', ['--date' => $date]);
        echo Artisan::output();
        echo "Exit code: {$exit}\n\n";
    }

    echo "Done. DELETE this file now.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
