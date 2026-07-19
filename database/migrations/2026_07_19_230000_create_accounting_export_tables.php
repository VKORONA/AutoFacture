<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->unique();
            $table->string('accounting_mode', 20)->default('accrual');
            $table->string('export_profile', 40)->default('universal_csv');
            $table->string('sales_journal_code', 10)->default('VT');
            $table->string('bank_journal_code', 10)->default('BQ');
            $table->string('customer_control_account', 20)->default('411000');
            $table->string('sales_services_account', 20)->default('706000');
            $table->string('sales_goods_account', 20)->default('707000');
            $table->string('bank_account', 20)->default('512000');
            $table->string('rounding_account', 20)->default('658000');
            $table->json('vat_accounts')->nullable();
            $table->boolean('include_payments')->default(true);
            $table->boolean('include_documents')->default(true);
            $table->boolean('include_commercial_annexes')->default(false);
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });

        Schema::create('accounting_export_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('profile', 40);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('processing');
            $table->boolean('include_payments')->default(true);
            $table->boolean('include_documents')->default(true);
            $table->boolean('include_commercial_annexes')->default(false);
            $table->unsignedInteger('invoice_count')->default(0);
            $table->unsignedInteger('credit_note_count')->default(0);
            $table->unsignedInteger('payment_count')->default(0);
            $table->unsignedInteger('entry_count')->default(0);
            $table->bigInteger('total_debit')->default(0);
            $table->bigInteger('total_credit')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->string('archive_disk', 40)->default('local');
            $table->text('archive_path')->nullable();
            $table->string('archive_sha256', 64)->nullable();
            $table->json('manifest')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'period_start', 'period_end'], 'accounting_exports_company_period_idx');
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_export_batches');
        Schema::dropIfExists('accounting_settings');
    }
};
