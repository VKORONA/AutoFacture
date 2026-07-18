<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->string('settlement_status', 30)
                ->default('APPLIED')
                ->after('status');
            $table->unsignedBigInteger('applied_to_balance')
                ->default(0)
                ->after('total');
            $table->unsignedBigInteger('refundable_amount')
                ->default(0)
                ->after('applied_to_balance');
            $table->index(['company_id', 'settlement_status']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'settlement_status']);
            $table->dropColumn([
                'settlement_status',
                'applied_to_balance',
                'refundable_amount',
            ]);
        });
    }
};
