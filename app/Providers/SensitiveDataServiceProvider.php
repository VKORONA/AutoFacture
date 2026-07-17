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
            foreach ($attributes as $attribute) {
                $model->setRawAttributes(array_merge(
                    $model->getAttributes(),
                    [$attribute => $encrypter->decrypt($model->getAttributeFromArray($attribute))]
                ));
            }
        });

        $modelClass::saving(function (Model $model) use ($attributes, $encrypter): void {
            foreach ($attributes as $attribute) {
                $value = $model->getAttributeFromArray($attribute);

                if ($value !== null && $value !== '') {
                    $model->setAttribute($attribute, $encrypter->encrypt((string) $value));
                }
            }
        });

        $modelClass::saved(function (Model $model) use ($attributes, $encrypter): void {
            foreach ($attributes as $attribute) {
                $value = $model->getAttributeFromArray($attribute);
                $model->setRawAttributes(array_merge(
                    $model->getAttributes(),
                    [$attribute => $encrypter->decrypt($value)]
                ));
            }
        });
    }
}
