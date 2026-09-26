<?php
// One-time setup script — DELETE after use
if (($_GET['token'] ?? '') !== 'm2h2026setup') {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$root    = str_replace('/', DIRECTORY_SEPARATOR, dirname(__DIR__));
$phpCli  = str_replace('php-cgi.exe', 'php.exe', PHP_BINARY);
$artisan = $root . DIRECTORY_SEPARATOR . 'artisan';

echo "=== php-cli: {$phpCli}\n\n";

function runCmd(array $cmd, string $cwd): array {
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $desc, $pipes, $cwd);
    if (!is_resource($proc)) return ['proc_open failed', -1];
    $out  = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err  = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($proc);
    return [trim($out . "\n" . $err), $code];
}

foreach (['config:cache', 'route:cache', 'view:clear', 'cache:clear'] as $cmd) {
    echo "--- php artisan {$cmd}\n";
    [$o, $c] = runCmd([$phpCli, $artisan, $cmd], $root);
    echo $o . "\nExit code: {$c}\n\n";
}

echo "=== Done. Delete this file now.\n";
