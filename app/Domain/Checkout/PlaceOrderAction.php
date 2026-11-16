<?php

namespace App\Domain\Checkout;

use App\Domain\Orders\OrderRepository;
use App\Domain\Orders\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Places a cash-on-delivery order (C.4, C.6, C.11). It only coordinates: every rule lives in the
 * interface it calls, and the service container picks the concrete classes.
 */
class PlaceOrderAction
{
    public function __construct(
        private readonly StoreStatus $store,
        private readonly StockChecker $stock,
        private readonly DeliveryFeeCalculator $fees,
        private readonly PriceCalculator $prices,
        private readonly PaymentMethod $payment,
        private readonly OrderRepository $orders,
        private readonly OrderNotifier $notifier,
    ) {}

    /** @throws CheckoutRefused */
    public function execute(CheckoutData $data): Order
    {
        if (! $this->store->isOpen()) {
            throw CheckoutRefused::storePaused();                      // REQ-CHK-17
        }

        if ($data->cart->isEmpty()) {
            throw CheckoutRefused::emptyBag();
        }

        $unavailable = array_values(array_filter(
            $data->cart->lines,
            fn (CartLine $line) => ! $this->stock->isOrderable($line->color, $line->size),
        ));

        if ($unavailable !== []) {
            throw CheckoutRefused::unavailable($unavailable, $data->language);   // REQ-CHK-16
        }

        // Prices and the fee come from the database now, never from the browser (REQ-CHK-12, REQ-CHK-13).
        $fee = $this->fees->feeFor($data->city, $data->cart);

        $order = new Order([
            'customer_name' => $data->customerName,
            'phone' => $data->phone,
            'email' => $data->email,
            'delivery_city_id' => $data->city->id,
            'country' => $data->city->country,                                  // REQ-CHK-26
            'city_name' => $data->cityName(),
            'area' => $data->area,
            'address' => $data->address,
            'note' => $data->note,
            'status' => OrderStatus::New,
            'subtotal' => $this->prices->subtotal($data->cart)->toDecimal(),
            'delivery_fee' => $fee->toDecimal(),
            'total' => $this->prices->total($data->cart, $fee)->toDecimal(),   // in JOD (REQ-DMO-45)
            'payment_method' => $this->payment->key(),
            'language' => $data->language,                                    // REQ-CHK-20
            'placed_at' => now(),
        ]);

        // Each item keeps a copy of the product as ordered, named with its colour when the
        // product has several, in the order's language (REQ-DB-07, D.8.1).
        $order->setRelation('items', collect($data->cart->lines)->map(fn (CartLine $line) => new OrderItem([
            'product_id' => $line->product->id,
            'product_color_id' => $line->color->id,
            'product_name' => $line->color->name($data->language),
            'color_name' => (string) $line->color->color?->t('name', $data->language),
            'size' => $line->size?->label,
            'unit_price' => $line->product->price,
            'quantity' => $line->quantity,
        ])));

        $order = $this->orders->saveWithItems($order);                       // REQ-CHK-14, REQ-DB-06
        $this->payment->settle($order);
        $this->notifier->orderPlaced($order);                                 // REQ-CHK-18, REQ-CHK-19

        return $order;
    }
}
