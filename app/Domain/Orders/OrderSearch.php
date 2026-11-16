<?php

namespace App\Domain\Orders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Finds orders by order number, customer name or phone (REQ-ORD-02). A phone matches
 * however it is typed: "079 123 4567", "+962791234567" and "791234567" all find "+962 791234567".
 * A full order number the customer quotes ("#K7M2QX", "k7m2qx") finds only that order (REQ-ORD-34).
 */
final class OrderSearch
{
    /** Order numbers are 6 characters (OrderNumber). */
    public const NUMBER_LENGTH = 6;

    public static function apply(Builder $query, string $term): Builder
    {
        $term = trim($term);
        $number = self::normalizeNumber($term);

        if ($number !== null && (clone $query)->where('number', $number)->exists()) {
            return $query->where('number', $number);
        }

        $number = strtoupper(ltrim($term, '#'));
        $phones = self::phoneDigits($term);

        return $query->where(function (Builder $query) use ($term, $number, $phones) {
            $query->where('customer_name', 'like', '%'.self::escape($term).'%');

            if (preg_match('/^[A-Z0-9]{1,6}$/', $number)) {
                $query->orWhere('number', 'like', $number.'%');
            }

            foreach ($phones as $digits) {
                $query->orWhere('phone', 'like', '%'.$digits.'%');
            }
        });
    }

    /** The full order number in a typed term ("#k7m2qx " -> "K7M2QX"), or null when it is not one. */
    public static function normalizeNumber(string $term): ?string
    {
        $number = strtoupper((string) preg_replace('/\s+/', '', ltrim(trim($term), '#')));
        $number = ltrim($number, '#');

        return preg_match('/^[A-Z0-9]{'.self::NUMBER_LENGTH.'}$/', $number) ? $number : null;
    }

    /** The typed digits without leading zeros, and without a dial code typed in front of them. @return list<string> */
    private static function phoneDigits(string $term): array
    {
        $digits = ltrim((string) preg_replace('/\D/', '', $term), '0');

        if (strlen($digits) < 3 || preg_match('/[^\d\s()+\-.]/', $term)) {
            return [];
        }

        $found = [$digits];
        foreach (array_column(config('store.phone_countries'), 'dial') as $dial) {
            if (str_starts_with($digits, $dial) && strlen($digits) - strlen($dial) >= 3) {
                $found[] = substr($digits, strlen($dial));
            }
        }

        return array_values(array_unique($found));
    }

    /** % and _ typed by the admin are searched for as themselves. */
    private static function escape(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
