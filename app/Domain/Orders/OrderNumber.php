<?php

namespace App\Domain\Orders;

use Illuminate\Support\Str;

/** Order numbers and confirmation link tokens, both made on the server. */
final class OrderNumber
{
    /** No 0/O, 1/I or similar look-alikes, so a number read out on the phone is never misheard (REQ-DMO-73). */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const LENGTH = 6;

    /** At least 32 random characters (REQ-DB-32); the column holds 64. */
    public const TOKEN_LENGTH = 64;

    public static function generate(): string
    {
        $number = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $number .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $number;
    }

    public static function token(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }
}
