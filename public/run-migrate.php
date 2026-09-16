<?php
// ONE-TIME migration runner. DELETE THIS FILE after use.

// Read BACKUP_TOKEN from .env
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

// Remove any failed record of the fix migration so it can re-run cleanly
\Illuminate\Support\Facades\DB::table('migrations')
    ->where('migration', '2026_09_16_000002_fix_notifications_user_id_uuid')
    ->delete();
echo "Cleared stale migration record.\n";

// Run the UUID fix migration (drop FK → alter column → re-add FK)
$exit2 = \Illuminate\Support\Facades\Artisan::call('migrate', [
    '--path'  => 'database/migrations/2026_09_16_000002_fix_notifications_user_id_uuid.php',
    '--force' => true,
]);
echo "--- fix user_id column ---\n";
echo \Illuminate\Support\Facades\Artisan::output();
echo "Exit code: " . $exit2 . "\n";
