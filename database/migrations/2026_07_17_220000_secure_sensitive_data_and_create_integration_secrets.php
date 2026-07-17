<?php

use Crater\Security\EncryptedAttribute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->text('iban')->nullable()->change();
            $table->text('bic')->nullable()->change();
        });

        Schema::table('file_disks', function (Blueprint $table): void {
            $table->longText('credentials')->nullable()->change();
        });

        Schema::create('integration_secrets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 80);
            $table->string('name', 120)->default('default');
            $table->longText('secret');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'provider', 'name']);
        });

        $encrypter = app(EncryptedAttribute::class);

        DB::table('companies')
            ->select(['id', 'iban', 'bic'])
            ->orderBy('id')
            ->chunkById(100, function ($companies) use ($encrypter): void {
                foreach ($companies as $company) {
                    DB::table('companies')->where('id', $company->id)->update([
                        'iban' => $encrypter->encrypt($company->iban),
                        'bic' => $encrypter->encrypt($company->bic),
                    ]);
                }
            });

        DB::table('file_disks')
            ->select(['id', 'credentials'])
            ->orderBy('id')
            ->chunkById(100, function ($disks) use ($encrypter): void {
                foreach ($disks as $disk) {
                    DB::table('file_disks')->where('id', $disk->id)->update([
                        'credentials' => $encrypter->encrypt($disk->credentials),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_secrets');

        Schema::table('companies', function (Blueprint $table): void {
            $table->string('iban', 34)->nullable()->change();
            $table->string('bic', 11)->nullable()->change();
        });

        Schema::table('file_disks', function (Blueprint $table): void {
            $table->text('credentials')->nullable()->change();
        });
    }
};
