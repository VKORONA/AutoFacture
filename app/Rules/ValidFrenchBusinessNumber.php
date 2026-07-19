<?php

namespace Crater\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidFrenchBusinessNumber implements Rule
{
    private int $length;
    private string $label;

    public function __construct(int $length, string $label)
    {
        $this->length = $length;
        $this->label = $label;
    }

    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        $number = preg_replace('/\D+/', '', (string) $value);

        if (strlen($number) !== $this->length) {
            return false;
        }

        $sum = 0;
        $parity = strlen($number) % 2;

        foreach (str_split($number) as $index => $digit) {
            $digit = (int) $digit;

            if ($index % 2 === $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    public function message()
    {
        return "Le {$this->label} n'est pas valide.";
    }
}
