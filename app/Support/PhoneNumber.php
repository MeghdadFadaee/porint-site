<?php

namespace App\Support;

use IntlChar;

class PhoneNumber
{
    public static function dialString(string $number): string
    {
        $digits = preg_replace_callback('/\p{Nd}/u', fn (array $match): string => (string) IntlChar::digit($match[0]), $number);

        return preg_replace('/[^+0-9]/', '', $digits);
    }
}
