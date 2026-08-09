<?php
/**
 * Sunday Step 1: Kommo-imported leads temizliği
 * Siler: meta_lead_id IS NULL AND source != 'whatsapp'
 * (Meta webhook leadleri meta_lead_id'e sahip, WA leadleri source='whatsapp')
 *
 * URL: https://management.m2h.ge/_sunday_1_cleanup.php
 *      ?confirm=yes → sil
 */

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$query = DB::table('leads')
    ->whereNull('meta_lead_id')
    ->where('source', '!=', 'whatsapp');

$count = $query->count();

if ($_GET['confirm'] ?? '' !== 'yes') {
    echo "=== CLEANUP PREVIEW ===\n\n";
    echo "Silinecek lead sayısı : {$count}\n";
    echo "(meta_lead_id IS NULL AND source != 'whatsapp')\n\n";
    echo "Korunacaklar:\n";
    echo "  - Meta webhook leadleri (meta_lead_id dolu): " . DB::table('leads')->whereNotNull('meta_lead_id')->count() . "\n";
    echo "  - WhatsApp leadleri (source=whatsapp)      : " . DB::table('leads')->where('source', 'whatsapp')->count() . "\n\n";
    echo "Silmek için: ?confirm=yes\n";
    exit;
}

echo "=== CLEANUP ===\n";
echo "Başlangıç: " . date('Y-m-d H:i:s') . "\n\n";
echo "Siliniyor ({$count} lead)...\n";

// cascadeOnDelete otomatik olarak notes, lead_tags, lead_status_history, lead_activities siler
$deleted = $query->delete();

echo "Silindi: {$deleted} lead (ve bağlı tüm kayıtlar cascade ile)\n\n";

// Kalan
$remaining = DB::table('leads')->count();
$tagGroups = DB::table('tag_groups')->where('name', 'Kommo Tags')->first();
if ($tagGroups) {
    $orphanTags = DB::table('tags')->where('tag_group_id', $tagGroups->id)
        ->whereNotExists(fn($q) => $q->from('lead_tags')->whereColumn('lead_tags.tag_id', 'tags.id'))
        ->count();
    if ($orphanTags > 0) {
        DB::table('tags')->where('tag_group_id', $tagGroups->id)
            ->whereNotExists(fn($q) => $q->from('lead_tags')->whereColumn('lead_tags.tag_id', 'tags.id'))
            ->delete();
        echo "Sahipsiz Kommo Tags silindi: {$orphanTags}\n";
    }
}

echo "Kalan lead    : {$remaining}\n";
echo "Bitiş         : " . date('Y-m-d H:i:s') . "\n\n";
echo "Sonraki adım  : _sunday_2_import.php\n";
