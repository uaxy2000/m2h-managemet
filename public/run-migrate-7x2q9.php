<?php
/**
 * TEMPORARY — DELETE THIS FILE IMMEDIATELY AFTER USE
 * Runs: php artisan migrate --force
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['secret'] ?? '') === 'migrate2026') {
    define('LARAVEL_START', microtime(true));
    require __DIR__ . '/../vendor/autoload.php';
    $app    = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->call('migrate', ['--force' => true]);
    $output = $kernel->output();
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;font-size:13px;white-space:pre-wrap;">';
    echo htmlspecialchars($output);
    echo '</pre>';
    exit;
}
?>
<!doctype html>
<html>
<head><title>Run Migration</title></head>
<body style="font-family:sans-serif;max-width:500px;margin:60px auto;padding:20px">
    <h2>Run: php artisan migrate --force</h2>
    <p style="color:#c00"><strong>DELETE this file after use!</strong></p>
    <form method="POST">
        <label>Secret: <input type="password" name="secret" style="width:220px;padding:6px"></label><br><br>
        <button type="submit" style="padding:8px 20px;background:#4f46e5;color:white;border:none;border-radius:6px;cursor:pointer">
            Run Migration
        </button>
    </form>
</body>
</html>
