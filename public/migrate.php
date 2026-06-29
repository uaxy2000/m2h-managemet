<?php

/**
 * One-time migration runner for Plesk (no SSH).
 * DELETE THIS FILE immediately after use.
 */

define('SECRET', 'M2H-migrate-2026');

if (($_GET['secret'] ?? '') !== SECRET) {
    http_response_code(403);
    die('403 Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';

$app    = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('migrate', ['--force' => true]);
$output = $kernel->output();

header('Content-Type: text/plain; charset=utf-8');
echo $output;
echo "\n--- Done. DELETE /public/migrate.php now! ---\n";
