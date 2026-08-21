<?php
/**
 * One-time migration: add document_path + status columns to finance tables
 * Key: finDocs2026
 * Run once, then delete.
 */
if (($_GET['key'] ?? '') !== 'finDocs2026') {
    http_response_code(403);
    die('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdo = \DB::getPdo();
$results = [];

$alters = [
    'expenses.document_path' => [
        'check' => "SHOW COLUMNS FROM `expenses` LIKE 'document_path'",
        'sql'   => "ALTER TABLE `expenses`
                    ADD COLUMN `document_path` VARCHAR(500) NULL AFTER `description`,
                    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER `document_path`",
        'label' => 'expenses.document_path + status',
    ],
    'incomes.document_path' => [
        'check' => "SHOW COLUMNS FROM `incomes` LIKE 'document_path'",
        'sql'   => "ALTER TABLE `incomes` ADD COLUMN `document_path` VARCHAR(500) NULL AFTER `description`",
        'label' => 'incomes.document_path',
    ],
    'payments.document_path' => [
        'check' => "SHOW COLUMNS FROM `payments` LIKE 'document_path'",
        'sql'   => "ALTER TABLE `payments` ADD COLUMN `document_path` VARCHAR(500) NULL AFTER `note`",
        'label' => 'payments.document_path',
    ],
];

foreach ($alters as $alter) {
    try {
        $cols = $pdo->query($alter['check'])->fetchAll();
        if (empty($cols)) {
            $pdo->exec($alter['sql']);
            $results[] = '✅ ' . $alter['label'] . ' added.';
        } else {
            $results[] = '⚠️  ' . $alter['label'] . ' already exists — skipped.';
        }
    } catch (\Throwable $e) {
        $results[] = '❌ ' . $alter['label'] . ': ' . $e->getMessage();
    }
}

// Ensure finance-docs storage directory exists
try {
    $dirs = ['finance-docs/expenses', 'finance-docs/incomes', 'finance-docs/payments'];
    foreach ($dirs as $dir) {
        \Storage::disk('local')->makeDirectory($dir);
    }
    $results[] = '✅ Storage directories created/verified.';
} catch (\Throwable $e) {
    $results[] = '⚠️  Storage dirs: ' . $e->getMessage();
}

// Clear caches
try {
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    $results[] = '✅ Caches cleared.';
} catch (\Throwable $e) {
    $results[] = '⚠️  Cache: ' . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo "finance-docs-deploy.php\n" . str_repeat('=', 40) . "\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nDone. Delete this file.\n";
