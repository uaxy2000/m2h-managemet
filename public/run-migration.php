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
    $done = [];

    // 1. wa_templates table
    $tables = array_column(DB::select("SHOW TABLES LIKE 'wa_templates'"), array_key_first((array)(DB::select("SHOW TABLES LIKE 'wa_templates'")[0] ?? new stdClass)));
    $waExists = DB::select("SHOW TABLES LIKE 'wa_templates'");
    if (empty($waExists)) {
        DB::statement("
            CREATE TABLE `wa_templates` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
              `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
              `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MARKETING',
              `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'APPROVED',
              `components` json NOT NULL,
              `header_image_url` varchar(1000) COLLATE utf8mb4_unicode_ci NULL,
              `parameter_fields` json NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `synced_at` timestamp NULL,
              `created_at` timestamp NULL,
              `updated_at` timestamp NULL,
              UNIQUE KEY `wa_templates_name_unique` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $batch = (int) DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => '2026_07_29_111054_create_wa_templates_table',
            'batch'     => $batch,
        ]);
        $done[] = 'wa_templates table created';
    } else {
        $done[] = 'wa_templates already exists — skipped';
        if (!DB::table('migrations')->where('migration', '2026_07_29_111054_create_wa_templates_table')->exists()) {
            $batch = (int) DB::table('migrations')->max('batch') + 1;
            DB::table('migrations')->insert(['migration' => '2026_07_29_111054_create_wa_templates_table', 'batch' => $batch]);
        }
    }

    // 2. meta_form_mappings.wa_template_id
    $cols = DB::select("SHOW COLUMNS FROM `meta_form_mappings` LIKE 'wa_template_id'");
    if (empty($cols)) {
        DB::statement("ALTER TABLE `meta_form_mappings` ADD COLUMN `wa_template_id` bigint unsigned NULL AFTER `assigned_to`");
        // Add FK only if wa_templates id is bigint (compatible)
        try {
            DB::statement("ALTER TABLE `meta_form_mappings` ADD CONSTRAINT `meta_form_mappings_wa_template_id_foreign` FOREIGN KEY (`wa_template_id`) REFERENCES `wa_templates` (`id`) ON DELETE SET NULL");
        } catch (\Throwable $e) {
            $done[] = 'FK skipped: ' . $e->getMessage();
        }
        $batch = (int) DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => '2026_07_29_111055_add_wa_template_id_to_meta_form_mappings',
            'batch'     => $batch,
        ]);
        $done[] = 'meta_form_mappings.wa_template_id column added';
    } else {
        $done[] = 'meta_form_mappings.wa_template_id already exists — skipped';
        if (!DB::table('migrations')->where('migration', '2026_07_29_111055_add_wa_template_id_to_meta_form_mappings')->exists()) {
            $batch = (int) DB::table('migrations')->max('batch') + 1;
            DB::table('migrations')->insert(['migration' => '2026_07_29_111055_add_wa_template_id_to_meta_form_mappings', 'batch' => $batch]);
        }
    }

    echo "Done!\n\n";
    foreach ($done as $line) {
        echo "✓ $line\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
