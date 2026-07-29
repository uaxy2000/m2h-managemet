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

    // Drop old wa_templates (old schema from previous session, no data)
    DB::statement("DROP TABLE IF EXISTS `wa_templates`");
    $done[] = 'Old wa_templates dropped';

    // Recreate with correct schema
    DB::statement("
        CREATE TABLE `wa_templates` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
          `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NULL,
          `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
          `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MARKETING',
          `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'APPROVED',
          `components` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
          `header_image_url` varchar(1000) COLLATE utf8mb4_unicode_ci NULL,
          `parameter_fields` longtext COLLATE utf8mb4_unicode_ci NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `synced_at` timestamp NULL,
          `created_at` timestamp NULL,
          `updated_at` timestamp NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `wa_templates_name_unique` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
    ");
    $done[] = 'wa_templates recreated with correct schema';

    // Update migration record
    DB::table('migrations')->where('migration', '2026_07_29_111054_create_wa_templates_table')->delete();
    $batch = (int) DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert([
        'migration' => '2026_07_29_111054_create_wa_templates_table',
        'batch'     => $batch,
    ]);

    // meta_form_mappings.wa_template_id — add FK now that id types match
    $hasFk = DB::select("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'meta_form_mappings'
          AND CONSTRAINT_NAME = 'meta_form_mappings_wa_template_id_foreign'
    ");
    if (empty($hasFk)) {
        try {
            DB::statement("
                ALTER TABLE `meta_form_mappings`
                ADD CONSTRAINT `meta_form_mappings_wa_template_id_foreign`
                FOREIGN KEY (`wa_template_id`) REFERENCES `wa_templates` (`id`) ON DELETE SET NULL
            ");
            $done[] = 'FK meta_form_mappings → wa_templates added';
        } catch (\Throwable $e) {
            $done[] = 'FK skipped (non-critical): ' . $e->getMessage();
        }
    } else {
        $done[] = 'FK already exists';
    }

    // Ensure migration record exists for the FK migration too
    if (!DB::table('migrations')->where('migration', '2026_07_29_111055_add_wa_template_id_to_meta_form_mappings')->exists()) {
        DB::table('migrations')->insert([
            'migration' => '2026_07_29_111055_add_wa_template_id_to_meta_form_mappings',
            'batch'     => $batch,
        ]);
    }

    echo "Done!\n\n";
    foreach ($done as $line) {
        echo "✓ $line\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
