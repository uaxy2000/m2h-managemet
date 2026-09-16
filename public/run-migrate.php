<?php
// ONE-TIME migration runner. DELETE THIS FILE after use.

$envPath = __DIR__ . '/../.env';
$token = '';
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), 'BACKUP_TOKEN=')) {
        $token = trim(substr($line, strpos($line, '=') + 1), " \t\"'");
        break;
    }
}

$provided = $_GET['token'] ?? '';
if (!$token || !$provided || !hash_equals($token, $provided)) {
    http_response_code(403);
    exit('Forbidden');
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

// Clear stale record so it re-runs if it failed before
\Illuminate\Support\Facades\DB::table('migrations')
    ->where('migration', '2026_09_16_000003_add_missing_columns_to_notifications')
    ->delete();

$exit = \Illuminate\Support\Facades\Artisan::call('migrate', [
    '--path'  => 'database/migrations/2026_09_16_000003_add_missing_columns_to_notifications.php',
    '--force' => true,
]);
echo \Illuminate\Support\Facades\Artisan::output();
echo "\nExit code: " . $exit;
