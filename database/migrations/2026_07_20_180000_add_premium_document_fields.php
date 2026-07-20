<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('project_name')->nullable();
            $table->string('project_address', 500)->nullable();
            $table->string('purchase_order_number', 100)->nullable();
            $table->string('project_contact')->nullable();
            $table->string('payment_terms_label')->nullable();
            $table->boolean('show_sepa_qr')->default(true);
        });

        Schema::table('estimates', function (Blueprint $table): void {
            $table->string('project_name')->nullable();
            $table->string('project_address', 500)->nullable();
            $table->string('purchase_order_number', 100)->nullable();
            $table->string('project_contact')->nullable();
            $table->string('payment_terms_label')->nullable();
            $table->boolean('show_sepa_qr')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table): void {
            $table->dropColumn([
                'project_name',
                'project_address',
                'purchase_order_number',
                'project_contact',
                'payment_terms_label',
                'show_sepa_qr',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'project_name',
                'project_address',
                'purchase_order_number',
                'project_contact',
                'payment_terms_label',
                'show_sepa_qr',
            ]);
        });
    }
};
