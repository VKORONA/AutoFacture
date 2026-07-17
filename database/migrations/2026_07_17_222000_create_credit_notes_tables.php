<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedInteger('currency_id');
            $table->string('credit_note_number', 40);
            $table->unsignedBigInteger('sequence_number');
            $table->string('unique_hash')->nullable()->unique();
            $table->date('issue_date');
            $table->text('reason');
            $table->string('status', 20)->default('ISSUED');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total');
            $table->timestamp('finalized_at');
            $table->string('immutable_hash', 64)->unique();
            $table->longText('finalized_snapshot');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->restrictOnDelete();
            $table->unique(['company_id', 'credit_note_number']);
            $table->index(['company_id', 'invoice_id']);
        });

        Schema::create('credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('invoice_item_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
