<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFrenchLegalFieldsToCompaniesAndCustomers extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_form', 40)->nullable();
            $table->string('siren', 9)->nullable()->index();
            $table->string('siret', 14)->nullable()->index();
            $table->string('vat_number', 20)->nullable();
            $table->string('ape_code', 8)->nullable();
            $table->string('rcs_city', 100)->nullable();
            $table->decimal('share_capital', 15, 2)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('vat_regime', 40)->default('standard');
            $table->boolean('vat_exempt')->default(false);
            $table->string('electronic_invoicing_email')->nullable();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('business');
            $table->string('siren', 9)->nullable()->index();
            $table->string('siret', 14)->nullable()->index();
            $table->string('vat_number', 20)->nullable();
            $table->string('ape_code', 8)->nullable();
            $table->string('electronic_invoicing_email')->nullable();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['siren']);
            $table->dropIndex(['siret']);
            $table->dropColumn([
                'customer_type',
                'siren',
                'siret',
                'vat_number',
                'ape_code',
                'electronic_invoicing_email',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['siren']);
            $table->dropIndex(['siret']);
            $table->dropColumn([
                'legal_form',
                'siren',
                'siret',
                'vat_number',
                'ape_code',
                'rcs_city',
                'share_capital',
                'iban',
                'bic',
                'vat_regime',
                'vat_exempt',
                'electronic_invoicing_email',
            ]);
        });
    }
}
