<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table): void {
            $table->uuid('line_uuid')->nullable()->after('estimate_id');
            $table->index(['estimate_id', 'line_uuid']);
        });

        DB::table('estimate_items')
            ->select('id')
            ->whereNull('line_uuid')
            ->orderBy('id')
            ->chunkById(250, function ($items): void {
                foreach ($items as $item) {
                    DB::table('estimate_items')
                        ->where('id', $item->id)
                        ->update(['line_uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('estimate_items', function (Blueprint $table): void {
            $table->uuid('line_uuid')->nullable(false)->change();
        });

        Schema::table('estimates', function (Blueprint $table): void {
            $table->string('annex_title')->nullable()->after('notes');
            $table->text('annex_notes')->nullable()->after('annex_title');
            $table->boolean('include_photo_annex')->default(true)->after('annex_notes');
        });

        Schema::create('estimate_line_photos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('estimate_id')->nullable();
            $table->uuid('line_uuid');
            $table->uuid('draft_token')->nullable();
            $table->string('disk', 40)->default('local');
            $table->string('original_name');
            $table->string('mime_type', 80)->default('image/jpeg');
            $table->string('image_path');
            $table->string('preview_path');
            $table->string('thumbnail_path');
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->default(1600);
            $table->unsignedInteger('height')->default(1200);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('estimate_id')->references('id')->on('estimates')->cascadeOnDelete();
            $table->index(['company_id', 'draft_token']);
            $table->index(['estimate_id', 'line_uuid', 'sort_order'], 'estimate_photo_line_order');
        });

        Schema::create('estimate_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('estimate_id')->nullable();
            $table->uuid('draft_token')->nullable();
            $table->string('disk', 40)->default('local');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 120);
            $table->string('path');
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('include_in_email')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('estimate_id')->references('id')->on('estimates')->cascadeOnDelete();
            $table->index(['company_id', 'draft_token']);
            $table->index(['estimate_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_attachments');
        Schema::dropIfExists('estimate_line_photos');

        Schema::table('estimates', function (Blueprint $table): void {
            $table->dropColumn(['annex_title', 'annex_notes', 'include_photo_annex']);
        });

        Schema::table('estimate_items', function (Blueprint $table): void {
            $table->dropIndex(['estimate_id', 'line_uuid']);
            $table->dropColumn('line_uuid');
        });
    }
};
