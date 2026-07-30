<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pdo = DB::connection()->getPdo();

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}

echo "<pre>\n";

// boards
if (tableExists($pdo, 'boards')) {
    echo "✓ boards already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `boards` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title` varchar(191) NOT NULL,
        `description` text NULL,
        `created_by` char(36) NOT NULL,
        `created_at` timestamp NULL,
        `updated_at` timestamp NULL,
        INDEX `boards_created_by_index` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ boards created\n";
}

// board_permissions
if (tableExists($pdo, 'board_permissions')) {
    echo "✓ board_permissions already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `board_permissions` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `board_id` bigint unsigned NOT NULL,
        `role` varchar(50) NOT NULL,
        `can_read` tinyint(1) NOT NULL DEFAULT 0,
        `can_write` tinyint(1) NOT NULL DEFAULT 0,
        UNIQUE KEY `board_permissions_board_id_role_unique` (`board_id`, `role`),
        CONSTRAINT `bp_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ board_permissions created\n";
}

// board_cards
if (tableExists($pdo, 'board_cards')) {
    echo "✓ board_cards already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `board_cards` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `board_id` bigint unsigned NOT NULL,
        `title` varchar(191) NOT NULL,
        `body` text NULL,
        `sort_order` int NOT NULL DEFAULT 0,
        `created_by` char(36) NOT NULL,
        `created_at` timestamp NULL,
        `updated_at` timestamp NULL,
        INDEX `board_cards_created_by_index` (`created_by`),
        CONSTRAINT `bc_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ board_cards created\n";
}

// card_permissions
if (tableExists($pdo, 'card_permissions')) {
    echo "✓ card_permissions already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `card_permissions` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `card_id` bigint unsigned NOT NULL,
        `role` varchar(50) NOT NULL,
        `can_read` tinyint(1) NOT NULL DEFAULT 0,
        `can_write` tinyint(1) NOT NULL DEFAULT 0,
        UNIQUE KEY `card_permissions_card_id_role_unique` (`card_id`, `role`),
        CONSTRAINT `cp_card_fk` FOREIGN KEY (`card_id`) REFERENCES `board_cards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ card_permissions created\n";
}

// card_notes
if (tableExists($pdo, 'card_notes')) {
    echo "✓ card_notes already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `card_notes` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `card_id` bigint unsigned NOT NULL,
        `content` text NOT NULL,
        `created_by` char(36) NOT NULL,
        `created_at` timestamp NULL,
        `updated_at` timestamp NULL,
        INDEX `card_notes_created_by_index` (`created_by`),
        CONSTRAINT `cn_card_fk` FOREIGN KEY (`card_id`) REFERENCES `board_cards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ card_notes created\n";
}

// card_tasks
if (tableExists($pdo, 'card_tasks')) {
    echo "✓ card_tasks already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `card_tasks` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `card_id` bigint unsigned NOT NULL,
        `title` varchar(191) NOT NULL,
        `assigned_to` char(36) NULL,
        `due_at` datetime NULL,
        `is_done` tinyint(1) NOT NULL DEFAULT 0,
        `created_by` char(36) NOT NULL,
        `created_at` timestamp NULL,
        `updated_at` timestamp NULL,
        INDEX `card_tasks_created_by_index` (`created_by`),
        INDEX `card_tasks_assigned_to_index` (`assigned_to`),
        CONSTRAINT `ct_card_fk` FOREIGN KEY (`card_id`) REFERENCES `board_cards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ card_tasks created\n";
}

// board_user_reads
if (tableExists($pdo, 'board_user_reads')) {
    echo "✓ board_user_reads already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `board_user_reads` (
        `user_id` char(36) NOT NULL,
        `board_id` bigint unsigned NOT NULL,
        `last_read_at` datetime NOT NULL,
        PRIMARY KEY (`user_id`, `board_id`),
        CONSTRAINT `bur_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ board_user_reads created\n";
}

// board_members
if (tableExists($pdo, 'board_members')) {
    echo "✓ board_members already exists — skipped\n";
} else {
    $pdo->exec("CREATE TABLE `board_members` (
        `board_id` bigint unsigned NOT NULL,
        `user_id` char(36) NOT NULL,
        `can_write` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`board_id`, `user_id`),
        CONSTRAINT `bm_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE,
        CONSTRAINT `bm_user_fk`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    echo "✓ board_members created\n";
}

// Mark migrations as run
$batch = (int) $pdo->query("SELECT COALESCE(MAX(batch), 0) FROM migrations")->fetchColumn();

$exists1 = $pdo->query("SELECT COUNT(*) FROM migrations WHERE migration = '2026_07_30_000001_create_boards_tables'")->fetchColumn();
if (!$exists1) {
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2026_07_30_000001_create_boards_tables', " . ($batch + 1) . ")");
    echo "✓ Migration 1 kaydedildi\n";
}

$exists2 = $pdo->query("SELECT COUNT(*) FROM migrations WHERE migration = '2026_07_30_000002_create_board_members_table'")->fetchColumn();
if (!$exists2) {
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2026_07_30_000002_create_board_members_table', " . ($batch + 1) . ")");
    echo "✓ Migration 2 kaydedildi\n";
}

echo "\nTamamlandı!\n</pre>";
