<?php
// ONE-TIME USE — DELETE AFTER RUNNING
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    // Check using SHOW COLUMNS (works on all MariaDB/MySQL versions)
    $columns = DB::select("SHOW COLUMNS FROM `lead_activities`");
    $colNames = array_column($columns, 'Field');

    if (in_array('is_read', $colNames)) {
        echo "Already migrated — columns exist.\n";
        exit;
    }

    DB::statement("
        ALTER TABLE `lead_activities`
          ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 1 AFTER `visible_to`,
          ADD COLUMN `read_at` timestamp NULL DEFAULT NULL AFTER `is_read`,
          ADD COLUMN `read_by` char(36) NULL DEFAULT NULL AFTER `read_at`,
          ADD CONSTRAINT `lead_activities_read_by_foreign`
            FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ");

    $batch = (int) DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert([
        'migration' => '2026_07_29_083630_add_read_tracking_to_lead_activities',
        'batch'     => $batch,
    ]);

    echo "Migration completed successfully.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
