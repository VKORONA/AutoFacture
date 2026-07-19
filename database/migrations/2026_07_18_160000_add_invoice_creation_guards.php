<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'client_request_id')) {
                $table->uuid('client_request_id')->nullable()->after('unique_hash');
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(
                ['company_id', 'invoice_number'],
                'invoices_company_invoice_number_index'
            );
            $table->unique(
                ['company_id', 'client_request_id'],
                'invoices_company_client_request_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_company_invoice_number_index');
            $table->dropUnique('invoices_company_client_request_unique');
            $table->dropColumn('client_request_id');
        });
    }
};
