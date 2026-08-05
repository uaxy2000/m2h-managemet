<?php
/**
 * Geçici migration scripti — todo_lists tablolarını oluşturur.
 * Çalıştırdıktan sonra bu dosyayı sil.
 * Kullanım: php _migrate_todo.php
 */

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

$migrationName = '2026_08_05_083136_create_todo_lists_tables';

// Zaten çalıştırılmış mı kontrol et
if (Schema::hasTable('todo_lists')) {
    echo "SKIP: todo_lists tablosu zaten mevcut.\n";
    exit(0);
}

if (DB::table('migrations')->where('migration', $migrationName)->exists()) {
    echo "SKIP: Migration kaydı zaten var.\n";
    exit(0);
}

echo "Migration başlıyor...\n";

Schema::create('todo_lists', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->uuid('created_by');
    $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
    $table->timestamps();
});
echo "  [OK] todo_lists\n";

Schema::create('todo_list_members', function (Blueprint $table) {
    $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
    $table->uuid('user_id');
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->primary(['todo_list_id', 'user_id']);
});
echo "  [OK] todo_list_members\n";

Schema::create('todo_list_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
    $table->text('body');
    $table->boolean('is_done')->default(false);
    $table->integer('sort_order')->default(0);
    $table->uuid('created_by');
    $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
    $table->uuid('completed_by')->nullable();
    $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
    $table->datetime('completed_at')->nullable();
    $table->timestamps();
});
echo "  [OK] todo_list_items\n";

Schema::create('todo_list_boards', function (Blueprint $table) {
    $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
    $table->foreignId('board_id')->constrained()->cascadeOnDelete();
    $table->primary(['todo_list_id', 'board_id']);
});
echo "  [OK] todo_list_boards\n";

// migrations tablosuna kaydet
$maxBatch = DB::table('migrations')->max('batch') ?? 0;
DB::table('migrations')->insert([
    'migration' => $migrationName,
    'batch'     => $maxBatch + 1,
]);
echo "  [OK] migrations tablosuna kaydedildi (batch " . ($maxBatch + 1) . ")\n";

echo "\nMigration tamamlandı! Bu dosyayı sil: _migrate_todo.php\n";
