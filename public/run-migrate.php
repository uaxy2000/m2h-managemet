<?php
// ONE-TIME: Recreate notifications table with correct schema. DELETE AFTER USE.
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    exit;
});

$envPath = __DIR__ . '/../.env';
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}

$token = $env['BACKUP_TOKEN'] ?? '';
$provided = $_GET['token'] ?? '';
if (!$token || !$provided || !hash_equals($token, $provided)) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain');

$dsn  = 'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1')
      . ';port='       . ($env['DB_PORT'] ?? '3306')
      . ';dbname='     . ($env['DB_DATABASE'] ?? '')
      . ';charset=utf8mb4';

$pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 1. Drop old broken table
$pdo->exec('DROP TABLE IF EXISTS `notifications`');
echo "Dropped notifications table.\n";

// 2. Create with correct schema (user_id as varchar(36) for UUID)
$pdo->exec("
    CREATE TABLE `notifications` (
        `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`    VARCHAR(36)     NOT NULL,
        `type`       VARCHAR(50)     NOT NULL,
        `title`      VARCHAR(255)    NOT NULL,
        `body`       VARCHAR(255)    NULL DEFAULT NULL,
        `url`        VARCHAR(255)    NULL DEFAULT NULL,
        `meta`       JSON            NULL,
        `read_at`    TIMESTAMP       NULL DEFAULT NULL,
        `created_at` TIMESTAMP       NULL DEFAULT NULL,
        `updated_at` TIMESTAMP       NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `notifications_user_read_created` (`user_id`, `read_at`, `created_at`),
        CONSTRAINT `notifications_user_id_foreign`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
");
echo "Created notifications table with correct schema.\n";

// 3. Mark all notification migrations as run in migrations table
$migrations = [
    '2026_09_16_000001_create_notifications_table',
    '2026_09_16_000002_fix_notifications_user_id_uuid',
    '2026_09_16_000003_add_missing_columns_to_notifications',
];

// Get current max batch
$batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM `migrations`')->fetchColumn();
$batch++;

$stmt = $pdo->prepare('INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES (?, ?)');
foreach ($migrations as $m) {
    // Remove first in case it exists with a different batch
    $pdo->prepare('DELETE FROM `migrations` WHERE `migration` = ?')->execute([$m]);
    $stmt->execute([$m, $batch]);
    echo "Recorded migration: $m\n";
}

echo "\nDone.";
