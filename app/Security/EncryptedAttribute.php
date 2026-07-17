<?php

namespace Crater\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class EncryptedAttribute
{
    private const PREFIX = 'enc:v1:';

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return self::PREFIX.Crypt::encryptString($value);
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '' || ! $this->isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::PREFIX)));
        } catch (DecryptException $exception) {
            report($exception);
            return null;
        }
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }
}
