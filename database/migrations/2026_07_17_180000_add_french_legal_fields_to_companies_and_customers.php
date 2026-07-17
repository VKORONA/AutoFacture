<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFrenchLegalFieldsToCompaniesAndCustomers extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_form', 40)->nullable()->after('name');
            $table->string('siren', 9)->nullable()->index()->after('legal_form');
            $table->string('siret', 14)->nullable()->index()->after('siren');
            $table->string('vat_number', 20)->nullable()->after('siret');
            $table->string('ape_code', 8)->nullable()->after('vat_number');
            $table->string('rcs_city', 100)->nullable()->after('ape_code');
            $table->decimal('share_capital', 15, 2)->nullable()->after('rcs_city');
            $table->string('iban', 34)->nullable()->after('share_capital');
            $table->string('bic', 11)->nullable()->after('iban');
            $table->string('vat_regime', 40)->default('standard')->after('bic');
            $table->boolean('vat_exempt')->default(false)->after('vat_regime');
            $table->string('electronic_invoicing_email')->nullable()->after('vat_exempt');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('business')->after('name');
            $table->string('siren', 9)->nullable()->index()->after('company_name');
            $table->string('siret', 14)->nullable()->index()->after('siren');
            $table->string('vat_number', 20)->nullable()->after('siret');
            $table->string('ape_code', 8)->nullable()->after('vat_number');
            $table->string('electronic_invoicing_email')->nullable()->after('email');
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
