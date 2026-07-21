<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('business_activity_type', 32)
                ->default('service_bic')
                ->after('description')
                ->index();
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->string('business_activity_type', 32)
                ->default('service_bic')
                ->after('description')
                ->index();
        });

        Schema::table('estimate_items', function (Blueprint $table): void {
            $table->string('business_activity_type', 32)
                ->default('service_bic')
                ->after('description')
                ->index();
        });

        Schema::create('micro_entrepreneur_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('declaration_frequency', 16)->default('monthly');
            $table->string('cfp_profile', 16)->default('commercial');
            $table->boolean('versement_liberatoire')->default(false);
            $table->boolean('acre_enabled')->default(false);
            $table->date('acre_end_date')->nullable();
            $table->decimal('acre_rate_factor', 6, 5)->default(0.50000);
            $table->json('rate_overrides')->nullable();
            $table->timestamp('rates_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('micro_turnover_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adjustment_date')->index();
            $table->string('business_activity_type', 32)->index();
            $table->bigInteger('amount');
            $table->string('label');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'adjustment_date'], 'micro_adjustments_company_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micro_turnover_adjustments');
        Schema::dropIfExists('micro_entrepreneur_settings');

        Schema::table('estimate_items', function (Blueprint $table): void {
            $table->dropIndex(['business_activity_type']);
            $table->dropColumn('business_activity_type');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex(['business_activity_type']);
            $table->dropColumn('business_activity_type');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex(['business_activity_type']);
            $table->dropColumn('business_activity_type');
        });
    }
};
