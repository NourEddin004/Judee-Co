<?php

namespace App\Support;

class Flag
{
    /** "JO" → 🇯🇴 */
    public static function emoji(string $countryCode): string
    {
        return implode('', array_map(
            fn (string $letter) => mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split(strtoupper($countryCode)),
        ));
    }
}
