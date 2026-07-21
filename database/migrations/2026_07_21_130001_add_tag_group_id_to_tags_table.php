<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (!Schema::hasColumn('tags', 'tag_group_id')) {
                $table->foreignUuid('tag_group_id')->nullable()->after('color')
                    ->constrained('tag_groups')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['tag_group_id']);
            $table->dropColumn('tag_group_id');
        });
    }
};
