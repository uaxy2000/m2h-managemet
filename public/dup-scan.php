<?php
// One-time duplicate lead scan — DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'm2h-dup-2026') {
    http_response_code(403); exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$dupEmails = DB::table('leads')
    ->whereNotNull('email')->where('email', '!=', '')
    ->select('email', DB::raw('COUNT(*) as cnt'))
    ->groupBy('email')->having('cnt', '>', 1)
    ->pluck('email');

$dupPhones = DB::table('leads')
    ->whereNotNull('phone')->where('phone', '!=', '')
    ->select('phone', DB::raw('COUNT(*) as cnt'))
    ->groupBy('phone')->having('cnt', '>', 1)
    ->pluck('phone');

$toFlag = DB::table('leads')
    ->where('is_duplicate_flag', false)
    ->where(function ($q) use ($dupEmails, $dupPhones) {
        if ($dupEmails->isNotEmpty()) $q->orWhereIn('email', $dupEmails);
        if ($dupPhones->isNotEmpty()) $q->orWhereIn('phone', $dupPhones);
    })->count();

$dry = ($_GET['run'] ?? '') !== '1';

if (!$dry) {
    $affected = DB::table('leads')
        ->where('is_duplicate_flag', false)
        ->where(function ($q) use ($dupEmails, $dupPhones) {
            if ($dupEmails->isNotEmpty()) $q->orWhereIn('email', $dupEmails);
            if ($dupPhones->isNotEmpty()) $q->orWhereIn('phone', $dupPhones);
        })->update(['is_duplicate_flag' => true]);
}

header('Content-Type: text/plain');
echo "Duplicate emails : " . $dupEmails->count() . "\n";
echo "Duplicate phones : " . $dupPhones->count() . "\n";
echo "Leads to flag    : " . $toFlag . "\n";
if ($dry) {
    echo "\n[DRY RUN] Add &run=1 to actually apply.\n";
} else {
    echo "Leads flagged    : " . $affected . "\n";
    echo "\nDone. Delete this file from Plesk now.\n";
}
