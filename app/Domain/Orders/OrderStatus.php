<?php

namespace App\Domain\Orders;

/** The five order statuses, in the order of their normal life (REQ-ORD-16, C.3). */
enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Confirmed => __('Confirmed'),
            self::OutForDelivery => __('Out for delivery'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * The admin colour name for the status badge. Calm on purpose: only "New", which needs the
     * owner, uses the JUDE rose; every other status is plain grey.
     */
    public function color(): string
    {
        return $this === self::New ? 'primary' : 'gray';
    }

    /** @return array<string, string> value => label, for menus and filters */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $status) => $status->value, self::cases()),
            array_map(fn (self $status) => $status->label(), self::cases()),
        );
    }

    /** Statuses that count as sales: every order except cancelled ones (as in the admin preview). */
    public static function sales(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status !== self::Cancelled));
    }
}
