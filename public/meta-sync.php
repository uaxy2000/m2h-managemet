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

    // Step 1: clear caches
    Artisan::call('config:clear'); echo "config:clear: " . trim(Artisan::output()) . "\n";
    Artisan::call('cache:clear');  echo "cache:clear: "  . trim(Artisan::output()) . "\n";
    Artisan::call('route:clear');  echo "route:clear: "  . trim(Artisan::output()) . "\n\n";

    // Step 2: check token
    echo "Token configured: " . (config('services.meta_ads.access_token') ? 'YES (' . strlen(config('services.meta_ads.access_token')) . ' chars)' : 'NO') . "\n";
    echo "Account ID: " . config('services.meta_ads.account_id') . "\n\n";

    // Step 3: create tables via SQL (artisan migrate unreliable on this host)
    $pdo = DB::connection()->getPdo();

    // meta_insights table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `meta_insights` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `date` DATE NOT NULL,
            `entity_type` VARCHAR(20) NOT NULL,
            `entity_id` VARCHAR(64) NOT NULL,
            `entity_name` VARCHAR(255) NULL,
            `parent_entity_id` VARCHAR(64) NULL,
            `spend` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `impressions` INT UNSIGNED NOT NULL DEFAULT 0,
            `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
            `leads_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `cpm` DECIMAL(10,4) NOT NULL DEFAULT 0,
            `cpc` DECIMAL(10,4) NOT NULL DEFAULT 0,
            `ctr` DECIMAL(8,4) NOT NULL DEFAULT 0,
            `synced_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `meta_insights_entity_date` (`entity_id`, `date`),
            KEY `meta_insights_type_date` (`entity_type`, `date`),
            KEY `meta_insights_parent_date` (`parent_entity_id`, `date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "meta_insights table: OK\n";

    // meta_adset_id column on leads
    $cols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'meta_adset_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `leads` ADD COLUMN `meta_adset_id` VARCHAR(64) NULL AFTER `meta_ad_id`");
        echo "leads.meta_adset_id column: ADDED\n";
    } else {
        echo "leads.meta_adset_id column: already exists\n";
    }
    echo "\n";

    // Step 4: sync
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
