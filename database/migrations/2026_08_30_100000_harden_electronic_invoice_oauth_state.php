<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronic_invoice_connections', function (Blueprint $table): void {
            $table->string('oauth_state_hash', 64)->nullable()->unique()->after('refresh_token');
            $table->timestamp('oauth_state_expires_at')->nullable()->after('oauth_state_hash');
        });

        DB::table('electronic_invoice_connections')
            ->where('connection_status', 'draft')
            ->update(['connection_status' => 'not_configured']);
        DB::table('electronic_invoice_connections')
            ->where('connection_status', 'configured')
            ->update(['connection_status' => 'credentials_saved']);
        DB::table('electronic_invoice_connections')
            ->where('connection_status', 'error')
            ->update(['connection_status' => 'connection_lost']);
    }

    public function down(): void
    {
        DB::table('electronic_invoice_connections')
            ->where('connection_status', 'not_configured')
            ->update(['connection_status' => 'draft']);
        DB::table('electronic_invoice_connections')
            ->whereIn('connection_status', ['credentials_saved', 'authorization_pending', 'token_refresh_required'])
            ->update(['connection_status' => 'configured']);
        DB::table('electronic_invoice_connections')
            ->where('connection_status', 'connection_lost')
            ->update(['connection_status' => 'error']);

        Schema::table('electronic_invoice_connections', function (Blueprint $table): void {
            $table->dropUnique(['oauth_state_hash']);
            $table->dropColumn(['oauth_state_hash', 'oauth_state_expires_at']);
        });
    }
};
