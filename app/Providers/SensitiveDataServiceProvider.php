<?php

namespace Crater\Providers;

use Crater\Models\Company;
use Crater\Models\FileDisk;
use Crater\Security\EncryptedAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class SensitiveDataServiceProvider extends ServiceProvider
{
    public function boot(EncryptedAttribute $encrypter): void
    {
        $this->protect(Company::class, ['iban', 'bic'], $encrypter);
        $this->protect(FileDisk::class, ['credentials'], $encrypter);
    }

    private function protect(string $modelClass, array $attributes, EncryptedAttribute $encrypter): void
    {
        $modelClass::retrieved(function (Model $model) use ($attributes, $encrypter): void {
            $raw = $model->getAttributes();

            foreach ($attributes as $attribute) {
                $raw[$attribute] = $encrypter->decrypt($raw[$attribute] ?? null);
            }

            $model->setRawAttributes($raw, true);
        });

        $modelClass::saving(function (Model $model) use ($attributes, $encrypter): void {
            $raw = $model->getAttributes();

            foreach ($attributes as $attribute) {
                $value = $raw[$attribute] ?? null;
                $raw[$attribute] = $encrypter->encrypt($value === null ? null : (string) $value);
            }

            $model->setRawAttributes($raw);
        });

        $modelClass::saved(function (Model $model) use ($attributes, $encrypter): void {
            $raw = $model->getAttributes();

            foreach ($attributes as $attribute) {
                $raw[$attribute] = $encrypter->decrypt($raw[$attribute] ?? null);
            }

            $model->setRawAttributes($raw, true);
        });
    }
}
