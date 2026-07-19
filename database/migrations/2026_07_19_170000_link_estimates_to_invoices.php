<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'source_estimate_id')) {
                $table->unsignedBigInteger('source_estimate_id')
                    ->nullable()
                    ->after('client_request_id');
            }
        });

        Schema::table('estimates', function (Blueprint $table): void {
            if (! Schema::hasColumn('estimates', 'converted_invoice_id')) {
                $table->unsignedBigInteger('converted_invoice_id')
                    ->nullable()
                    ->after('unique_hash');
            }

            if (! Schema::hasColumn('estimates', 'converted_at')) {
                $table->timestamp('converted_at')
                    ->nullable()
                    ->after('converted_invoice_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'source_estimate_id'],
                'invoices_company_source_estimate_unique'
            );
        });

        Schema::table('estimates', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'converted_invoice_id'],
                'estimates_company_converted_invoice_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table): void {
            $table->dropUnique('estimates_company_converted_invoice_unique');
            $table->dropColumn(['converted_invoice_id', 'converted_at']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('invoices_company_source_estimate_unique');
            $table->dropColumn('source_estimate_id');
        });
    }
};
