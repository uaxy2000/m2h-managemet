<?php
// One-time use — delete after running

// Read BACKUP_TOKEN from .env without bootstrapping Laravel
$token = '';
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, 'BACKUP_TOKEN=')) {
            $token = trim(substr($line, strlen('BACKUP_TOKEN=')), " \t\"'");
            break;
        }
    }
}

if (!$token || !hash_equals($token, ($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Forbidden');
}

$file = __DIR__ . '/../bootstrap/cache/config.php';

if (file_exists($file)) {
    unlink($file);
    echo "Config cache cleared.";
} else {
    echo "No config cache found — .env is already read directly.";
}
