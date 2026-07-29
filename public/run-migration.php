<?php
// ONE-TIME USE — DELETE AFTER RUNNING
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

header('Content-Type: text/plain; charset=utf-8');

try {
    if (Schema::hasColumn('lead_activities', 'is_read')) {
        echo "Already migrated — columns exist.\n";
        exit;
    }

    Schema::table('lead_activities', function (Blueprint $table) {
        $table->boolean('is_read')->default(true)->after('visible_to');
        $table->timestamp('read_at')->nullable()->after('is_read');
        $table->uuid('read_by')->nullable()->after('read_at');
        $table->foreign('read_by')->references('id')->on('users')->nullOnDelete();
    });

    $batch = DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert([
        'migration' => '2026_07_29_083630_add_read_tracking_to_lead_activities',
        'batch'     => $batch,
    ]);

    echo "Migration completed successfully.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
