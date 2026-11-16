<?php

namespace App\Support;

/**
 * The checkout phone rule, the same as the browser's (resources/js/store/phone.js). Only digits
 * count, Arabic-Indic digits as 0 to 9; a number that starts with the country's dial code (after
 * +, 00 or nothing) and is too long for the country loses that code once; what remains must have
 * exactly as many digits as the country uses, not counting a 0 at the start (REQ-CHK-21,
 * REQ-CHK-22, REQ-DMO-56, REQ-DMO-57, Appendix D.8.3).
 */
final class Phone
{
    private const ARABIC_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * The country of Appendix D.8.3 with this code, or the first one, Jordan.
     *
     * @return array{code: string, dial: string, digits: list<int>, flag: string, placeholder: string}
     */
    public static function country(mixed $code): array
    {
        $countries = config('store.phone_countries');

        return collect($countries)->firstWhere('code', $code) ?? $countries[0];
    }

    /** The digits the field keeps of what was typed or pasted; "+962 79 123 4567" → "791234567". */
    public static function digits(string $value, array $country): string
    {
        $digits = (string) preg_replace('/\D/', '', strtr($value, self::ARABIC_DIGITS));
        $dial = $country['dial'];
        $prefix = match (true) {
            str_starts_with($digits, '00'.$dial) => strlen($dial) + 2,
            str_starts_with($digits, $dial) => strlen($dial),
            default => 0,
        };

        return $prefix > 0 && strlen($digits) > max($country['digits']) ? substr($digits, $prefix) : $digits;
    }

    /** The number without the 0 at its start; "0791234567" → "791234567". */
    public static function national(string $value, array $country): string
    {
        $digits = self::digits($value, $country);

        return str_starts_with($digits, '0') ? substr($digits, 1) : $digits;
    }

    public static function isValid(string $value, array $country): bool
    {
        return in_array(strlen(self::national($value, $country)), $country['digits'], true);
    }

    /** "0791234567" with Jordan → "+962 791234567". */
    public static function international(string $value, array $country): string
    {
        return '+'.$country['dial'].' '.self::national($value, $country);
    }

    /** "Please enter 9 digits after +962." (REQ-DMO-70), in the page's language. */
    public static function invalidMessage(array $country): string
    {
        $lengths = $country['digits'];
        $digits = count($lengths) === 1
            ? (string) $lengths[0]
            : __('store.checkout.phone_digits_or', ['first' => $lengths[0], 'second' => $lengths[1]]);

        return trans_choice('store.checkout.invalid_phone', max($lengths), ['digits' => $digits, 'dial' => '+'.$country['dial']]);
    }
}
