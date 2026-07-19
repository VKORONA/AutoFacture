<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_line_photos', function (Blueprint $table): void {
            $table->text('pdf_path')->nullable()->after('thumbnail_path');
            $table->string('checksum_sha256', 64)->nullable()->after('pdf_path');
            $table->unsignedInteger('web_size_bytes')->nullable()->after('size_bytes');
            $table->unsignedInteger('pdf_size_bytes')->nullable()->after('web_size_bytes');
            $table->index(['company_id', 'checksum_sha256'], 'estimate_photos_company_checksum_idx');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_line_photos', function (Blueprint $table): void {
            $table->dropIndex('estimate_photos_company_checksum_idx');
            $table->dropColumn([
                'pdf_path',
                'checksum_sha256',
                'web_size_bytes',
                'pdf_size_bytes',
            ]);
        });
    }
};
