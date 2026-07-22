<?php
if (($_GET['key'] ?? '') !== 'migrate2026') {
    http_response_code(403); exit('Forbidden');
}
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo '<pre>';
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
echo '</pre>';
