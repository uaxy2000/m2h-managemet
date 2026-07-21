<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $col = DB::select("SHOW COLUMNS FROM notes WHERE Field = 'visibility'")[0] ?? null;
        if ($col && str_starts_with(strtolower($col->Type), 'enum')) {
            DB::statement("ALTER TABLE notes MODIFY COLUMN visibility VARCHAR(191) NOT NULL DEFAULT 'internal'");
            DB::statement("UPDATE notes SET visibility = 'client' WHERE visibility = 'shared'");
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notes MODIFY COLUMN visibility ENUM('internal','client','service_provider','agent') NOT NULL DEFAULT 'internal'");
    }
};
