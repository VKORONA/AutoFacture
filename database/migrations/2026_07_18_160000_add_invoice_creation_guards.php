<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateInvoiceNumber = DB::table('invoices')
            ->select('company_id', 'invoice_number', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('invoice_number')
            ->groupBy('company_id', 'invoice_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateInvoiceNumber) {
            throw new RuntimeException(
                'Des numéros de facture en double existent déjà pour une même entreprise. '
                .'Corrigez-les avant d’appliquer la contrainte d’unicité.'
            );
        }

        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'client_request_id')) {
                $table->uuid('client_request_id')->nullable()->after('unique_hash');
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'invoice_number'],
                'invoices_company_invoice_number_unique'
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
            $table->dropUnique('invoices_company_invoice_number_unique');
            $table->dropUnique('invoices_company_client_request_unique');
            $table->dropColumn('client_request_id');
        });
    }
};
