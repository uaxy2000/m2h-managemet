<?php
// ONE-TIME deployment helper — DELETE after use
if (($_GET['key'] ?? '') !== 'finance2026') { http_response_code(403); exit('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "<pre>\n";

// Clear caches
Artisan::call('config:clear');  echo "config:clear\n";
Artisan::call('route:clear');   echo "route:clear\n";
Artisan::call('view:clear');    echo "view:clear\n";

// Create tables
$pdo = DB::getPdo();

$pdo->exec("
CREATE TABLE IF NOT EXISTS transaction_categories (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    direction ENUM('expense','income') NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "transaction_categories: OK\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS financial_accounts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('bank','cash','current_person','current_company') NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    user_id CHAR(36) NULL,
    company_id CHAR(36) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (type),
    INDEX (user_id),
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "financial_accounts: OK\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS expenses (
    id CHAR(36) NOT NULL PRIMARY KEY,
    date DATE NOT NULL,
    category_id CHAR(36) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    description TEXT NULL,
    lead_id CHAR(36) NULL,
    paid_by_user_id CHAR(36) NULL,
    source_account_id CHAR(36) NULL,
    created_by CHAR(36) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (date),
    INDEX (category_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "expenses: OK\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS incomes (
    id CHAR(36) NOT NULL PRIMARY KEY,
    date DATE NOT NULL,
    category_id CHAR(36) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    description TEXT NULL,
    lead_id CHAR(36) NULL,
    company_id CHAR(36) NULL,
    target_account_id CHAR(36) NULL,
    created_by CHAR(36) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (date),
    INDEX (category_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "incomes: OK\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS account_transfers (
    id CHAR(36) NOT NULL PRIMARY KEY,
    date DATE NOT NULL,
    from_account_id CHAR(36) NOT NULL,
    to_account_id CHAR(36) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    description TEXT NULL,
    reference_type VARCHAR(255) NULL,
    reference_id CHAR(36) NULL,
    created_by CHAR(36) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "account_transfers: OK\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS account_movements (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NULL,
    movable_type VARCHAR(255) NULL,
    movable_id CHAR(36) NULL,
    created_by CHAR(36) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (account_id, date),
    INDEX (movable_type, movable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "account_movements: OK\n";

echo "\nAll done. DELETE this file from the server.\n</pre>";
