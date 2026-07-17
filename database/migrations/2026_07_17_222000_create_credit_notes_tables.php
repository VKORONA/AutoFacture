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
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
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
            $table->unique(['company_id', 'credit_note_number']);
            $table->index(['company_id', 'invoice_id']);
        });

        Schema::create('credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
