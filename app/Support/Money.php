<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * An exact JOD amount, kept as whole hundredths of a dinar so no rounding error can creep in
 * (REQ-DB-08), plus the price format the demo shows: "17 JD", "12.50 JD" (REQ-DMO-39, REQ-DMO-42).
 * The browser re-formats prices in USD when the customer picks it (REQ-DMO-40); orders are
 * always saved and charged in JOD (REQ-DMO-45).
 */
final class Money implements Stringable
{
    private function __construct(private readonly int $hundredths) {}

    /** From a database decimal such as "12.50", or a whole number. */
    public static function of(string|int $amount): self
    {
        if (is_int($amount)) {
            return new self($amount * 100);
        }

        if (preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', $amount, $parts) !== 1) {
            throw new InvalidArgumentException("Not a JOD amount: {$amount}");
        }

        return new self((int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '0', 2, '0'));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->hundredths + $other->hundredths);
    }

    public function times(int $quantity): self
    {
        return new self($this->hundredths * $quantity);
    }

    public function equals(self $other): bool
    {
        return $this->hundredths === $other->hundredths;
    }

    public function isLessThan(self $other): bool
    {
        return $this->hundredths < $other->hundredths;
    }

    /** "12.50", ready for a decimal column. */
    public function toDecimal(): string
    {
        return intdiv($this->hundredths, 100).'.'.str_pad((string) ($this->hundredths % 100), 2, '0', STR_PAD_LEFT);
    }

    public function format(): string
    {
        return self::jod($this->toDecimal());
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }

    /** A delivery fee: "Free" when it is zero (free delivery from 50 JD), else "3 JD". */
    public static function fee(self|string|int|float|null $amount): string
    {
        return $amount !== null && (float) (string) $amount === 0.0 ? __('store.free_delivery.free') : self::jod($amount);
    }

    public static function jod(self|string|int|float|null $amount): string
    {
        if ($amount === null) {
            return __('store.price_pending');
        }

        if ($amount instanceof self) {
            $amount = $amount->toDecimal();
        }

        $rounded = round((float) $amount, 2);
        $number = floor($rounded) == $rounded ? (string) (int) $rounded : number_format($rounded, 2, '.', ',');

        return $number.' JD';
    }
}
