<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('legal_form', 40)->nullable()->after('ape_code');
            $table->timestamp('registry_checked_at')->nullable()->after('legal_form');
            $table->string('registry_source', 100)->nullable()->after('registry_checked_at');
            $table->index(['company_id', 'siret'], 'customers_company_siret_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_company_siret_index');
            $table->dropColumn(['legal_form', 'registry_checked_at', 'registry_source']);
        });
    }
};
