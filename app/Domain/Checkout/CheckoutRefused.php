<?php

namespace App\Domain\Checkout;

use RuntimeException;

/** Checkout said no: the store is paused, or a piece in the bag can no longer be ordered. */
final class CheckoutRefused extends RuntimeException
{
    public const PAUSED = 'paused';

    public const UNAVAILABLE = 'unavailable';

    public const EMPTY = 'empty';

    /**
     * @param  list<string>  $products  names of the pieces that can no longer be ordered
     * @param  list<int>  $productIds  their product colour ids, so the browser can take them out of the bag
     */
    private function __construct(
        public readonly string $reason,
        public readonly array $products = [],
        public readonly array $productIds = [],
    ) {
        parent::__construct("Checkout refused: {$reason}");
    }

    public static function storePaused(): self
    {
        return new self(self::PAUSED);
    }

    public static function emptyBag(): self
    {
        return new self(self::EMPTY);
    }

    /** @param  list<CartLine>  $lines */
    public static function unavailable(array $lines, string $language): self
    {
        $colors = collect($lines)->map(fn (CartLine $line) => $line->color)->unique('id')->values();

        return new self(self::UNAVAILABLE, $colors->map(fn ($color) => $color->name($language))->all(), $colors->pluck('id')->all());
    }
}
