<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestamp('finalized_at')->nullable()->index();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('immutable_hash', 64)->nullable()->unique();
            $table->longText('finalized_snapshot')->nullable();
            $table->unsignedBigInteger('credited_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['finalized_by']);
            $table->dropColumn([
                'finalized_at',
                'finalized_by',
                'immutable_hash',
                'finalized_snapshot',
                'credited_amount',
            ]);
        });
    }
};
