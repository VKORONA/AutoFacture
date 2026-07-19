<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_invoice_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('superpdp');
            $table->string('environment', 20)->default('sandbox');
            $table->unsignedTinyInteger('setup_step')->default(1);
            $table->string('connection_status', 40)->default('draft');
            $table->timestamp('account_created_at')->nullable();
            $table->timestamp('company_verified_at')->nullable();
            $table->timestamp('credentials_configured_at')->nullable();
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('external_company_id')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'connection_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_invoice_connections');
    }
};
