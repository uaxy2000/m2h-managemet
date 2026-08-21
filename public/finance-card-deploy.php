<?php
if (($_GET['key'] ?? '') !== 'card2026') { http_response_code(403); die('Forbidden'); }

// Manual .env parser — handles APP_KEY=base64:... and quoted values
$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

try {
    $pdo->exec("ALTER TABLE financial_accounts ADD COLUMN card_last6 VARCHAR(6) NULL AFTER type");
    echo "✓ card_last6 column added to financial_accounts.<br>";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "• card_last6 already exists — skipped.<br>";
    } else {
        echo "✗ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

echo "Done.";
