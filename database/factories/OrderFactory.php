<?php

namespace Database\Factories;

use App\Domain\Orders\OrderNumber;
use App\Domain\Orders\OrderStatus;
use App\Models\DeliveryCity;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An order as checkout saves it. Tests that need its items add them with ->withItems().
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $city = DeliveryCity::query()->where('key', 'amman')->first() ?? DeliveryCity::query()->firstOrFail();

        return [
            'number' => OrderNumber::generate(),
            'link_token' => OrderNumber::token(),
            'customer_name' => fake()->firstName().' '.fake()->lastName(),
            'phone' => '+962 79'.fake()->numerify('#######'),
            'email' => null,
            'delivery_city_id' => $city->id,
            'city_name' => $city->name_en,
            'area' => 'Khalda',
            'address' => fake()->streetAddress(),
            'note' => null,
            'status' => OrderStatus::New,
            'subtotal' => '17.00',
            'delivery_fee' => $city->fee,
            'total' => number_format(17 + (float) $city->fee, 2, '.', ''),
            'payment_method' => 'cash_on_delivery',
            'language' => 'en',
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /** One line of 17 JOD, the default subtotal. */
    public function withItems(): static
    {
        return $this->afterCreating(fn (Order $order) => $order->items()->create([
            'product_id' => null,
            'product_name' => 'Rosemary Hijab',
            'color_name' => 'Rosemary',
            'size' => null,
            'unit_price' => '17.00',
            'quantity' => 1,
        ]));
    }
}
