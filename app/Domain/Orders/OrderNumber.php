<?php

namespace App\Domain\Orders;

use Illuminate\Support\Str;

/** Order numbers and confirmation link tokens, both made on the server. */
final class OrderNumber
{
    /**
     * Order numbers are digits only (the owner's choice, 20 Nov 2026), easy to read out on the phone and
     * to type on a phone's number pad. They never start with 0. Orders placed before keep their old
     * letters-and-digits numbers, and both kinds are still found by Track order and the admin search.
     */
    public const DIGITS = '0123456789';

    public const LENGTH = 6;

    /** At least 32 random characters (REQ-DB-32); the column holds 64. */
    public const TOKEN_LENGTH = 64;

    public static function generate(): string
    {
        return (string) random_int(10 ** (self::LENGTH - 1), 10 ** self::LENGTH - 1);
    }

    public static function token(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }
}
