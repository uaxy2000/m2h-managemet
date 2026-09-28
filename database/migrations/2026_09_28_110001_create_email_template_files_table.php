<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('email_template_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_files');
    }
};
