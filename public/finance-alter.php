<?php
/**
 * One-time migration: add parent_id to transaction_categories
 * Key: finance2026alter
 * Run once, then delete.
 */
if (($_GET['key'] ?? '') !== 'finance2026alter') {
    http_response_code(403);
    die('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdo = \DB::getPdo();

$results = [];

// 1. Add parent_id column if it doesn't exist
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `transaction_categories` LIKE 'parent_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `transaction_categories`
            ADD COLUMN `parent_id` CHAR(36) NULL DEFAULT NULL AFTER `id`,
            ADD CONSTRAINT `tc_parent_fk` FOREIGN KEY (`parent_id`)
                REFERENCES `transaction_categories`(`id`) ON DELETE RESTRICT");
        $results[] = '✅ parent_id column + FK added.';
    } else {
        $results[] = '⚠️  parent_id column already exists — skipped.';
    }
} catch (\Throwable $e) {
    $results[] = '❌ parent_id: ' . $e->getMessage();
}

// 2. Clear caches
try {
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    $results[] = '✅ Caches cleared.';
} catch (\Throwable $e) {
    $results[] = '⚠️  Cache clear: ' . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo "finance-alter.php results\n";
echo str_repeat('=', 40) . "\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nDone. Delete this file.\n";
