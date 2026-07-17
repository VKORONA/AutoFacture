<?php

namespace Crater\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidIban implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        $iban = strtoupper(preg_replace('/\s+/', '', (string) $value));

        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';

        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;
        }

        $remainder = 0;

        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    public function message()
    {
        return "L’IBAN n’est pas valide.";
    }
}
