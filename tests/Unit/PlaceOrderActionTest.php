<?php

namespace Tests\Unit;

use App\Domain\Checkout\Cart;
use App\Domain\Checkout\CartLine;
use App\Domain\Checkout\CartPriceCalculator;
use App\Domain\Checkout\CheckoutData;
use App\Domain\Checkout\CheckoutRefused;
use App\Domain\Checkout\DeliveryFeeCalculator;
use App\Domain\Checkout\OrderNotifier;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Checkout\PlaceOrderAction;
use App\Domain\Checkout\StockChecker;
use App\Domain\Checkout\StoreStatus;
use App\Domain\Orders\OrderRepository;
use App\Domain\Orders\OrderStatus;
use App\Models\Color;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Size;
use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PlaceOrderAction with every rule swapped for a stand-in, and no database (C.11): the action only
 * coordinates, so each rule can change without touching it.
 */
class PlaceOrderActionTest extends TestCase
{
    protected bool $seed = false;

    private bool $open = true;

    /** @var list<int> product colours the stock stand-in refuses */
    private array $soldOut = [];

    /** @var list<string> what happened, in order */
    private array $calls = [];

    private function action(): PlaceOrderAction
    {
        $test = $this;

        return new PlaceOrderAction(
            new class($test) implements StoreStatus
            {
                public function __construct(private PlaceOrderActionTest $test) {}

                public function isOpen(): bool
                {
                    return $this->test->isOpen();
                }
            },
            new class($test) implements StockChecker
            {
                public function __construct(private PlaceOrderActionTest $test) {}

                public function isOrderable(ProductColor $color, ?Size $size): bool
                {
                    return $this->test->isOrderable($color);
                }
            },
            new class implements DeliveryFeeCalculator
            {
                public function feeFor(DeliveryCity $city, Cart $cart): Money
                {
                    return Money::of('3.00');
                }
            },
            new CartPriceCalculator,
            new class($test) implements PaymentMethod
            {
                public function __construct(private PlaceOrderActionTest $test) {}

                public function key(): string
                {
                    return 'cash_on_delivery';
                }

                public function settle(Order $order): void
                {
                    $this->test->record('settled '.$order->number);
                }
            },
            new class($test) implements OrderRepository
            {
                public function __construct(private PlaceOrderActionTest $test) {}

                public function saveWithItems(Order $order): Order
                {
                    $order->number = 'TEST23';
                    $this->test->record('saved '.$order->items->count().' items');

                    return $order;
                }

                public function findByToken(string $token): ?Order
                {
                    return null;
                }
            },
            new class($test) implements OrderNotifier
            {
                public function __construct(private PlaceOrderActionTest $test) {}

                public function orderPlaced(Order $order): void
                {
                    $this->test->record('emailed '.$order->number);
                }
            },
        );
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function isOrderable(ProductColor $color): bool
    {
        return ! in_array($color->id, $this->soldOut, true);
    }

    public function record(string $call): void
    {
        $this->calls[] = $call;
    }

    /** One colour of a product, in memory. A product with 2 or more colours names its items with the colour (D.8.1). */
    private function color(int $id, int $productId, string $english, string $arabic, string $price, array $color, int $colors = 1): ProductColor
    {
        $product = new Product(['name_en' => $english, 'name_ar' => $arabic, 'price' => $price]);
        $product->id = $productId;
        $product->setAttribute('colors_count', $colors);

        $productColor = new ProductColor(['in_stock' => true]);
        $productColor->id = $id;
        $productColor->setRelation('product', $product);

        return $productColor->setRelation('color', new Color(['name_en' => $color[0], 'name_ar' => $color[1]]));
    }

    private function checkout(string $language = 'en'): CheckoutData
    {
        $city = new DeliveryCity(['key' => 'irbid', 'name_en' => 'Irbid', 'name_ar' => 'إربد', 'fee' => '3.00']);
        $city->id = 4;

        return new CheckoutData(
            customerName: 'Lina Khalil', phone: '+962 79 123 4567', email: null, city: $city,
            area: 'Al Husn', address: 'Street 5, building 2', note: null, language: $language,
            cart: new Cart([
                new CartLine($this->color(12, 2, 'Rosemary Hijab', 'حجاب روزماري', '17.00', ['Rosemary', 'روزماري']), null, 2),
                new CartLine($this->color(31, 30, 'Wrap Set', 'طقم بربطة جانبية', '35.00', ['Black', 'أسود'], colors: 4), new Size(['label' => 'M']), 1),
            ]),
        );
    }

    #[Test]
    public function req_chk_17_a_paused_store_refuses_before_anything_is_saved(): void
    {
        $this->open = false;

        try {
            $this->action()->execute($this->checkout());
            $this->fail('A paused store took an order.');
        } catch (CheckoutRefused $refused) {
            $this->assertSame(CheckoutRefused::PAUSED, $refused->reason);
        }
        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function req_chk_16_pieces_that_can_no_longer_be_ordered_are_refused_by_name_and_id(): void
    {
        $this->soldOut = [12, 31];

        try {
            $this->action()->execute($this->checkout('ar'));
            $this->fail('A sold-out piece was ordered.');
        } catch (CheckoutRefused $refused) {
            // Product colour ids, so the browser takes just those colours out of the bag.
            $this->assertSame([CheckoutRefused::UNAVAILABLE, ['حجاب روزماري', 'طقم بربطة جانبية أسود'], [12, 31]], [$refused->reason, $refused->products, $refused->productIds]);
        }
        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function req_chk_12_req_chk_13_req_chk_14_req_chk_18_the_order_is_priced_saved_paid_and_announced(): void
    {
        $order = $this->action()->execute($this->checkout());

        $this->assertSame(['69.00', '3.00', '72.00'], [$order->subtotal, $order->delivery_fee, $order->total]);   // 2 × 17 + 35, then + 3
        $this->assertSame([OrderStatus::New, 'cash_on_delivery', 'Irbid', 4], [$order->status, $order->payment_method, $order->city_name, $order->delivery_city_id]);
        $this->assertSame(['saved 2 items', 'settled TEST23', 'emailed TEST23'], $this->calls);
    }

    #[Test]
    public function req_db_07_req_chk_20_items_keep_a_copy_of_each_piece_named_with_its_color_in_the_customers_language(): void
    {
        $order = $this->action()->execute($this->checkout('ar'));

        $this->assertSame([
            ['product_id' => 2, 'product_color_id' => 12, 'product_name' => 'حجاب روزماري', 'color_name' => 'روزماري', 'size' => null, 'unit_price' => '17.00', 'quantity' => 2],
            ['product_id' => 30, 'product_color_id' => 31, 'product_name' => 'طقم بربطة جانبية أسود', 'color_name' => 'أسود', 'size' => 'M', 'unit_price' => '35.00', 'quantity' => 1],
        ], $order->items->map(fn ($item) => $item->only(['product_id', 'product_color_id', 'product_name', 'color_name', 'size', 'unit_price', 'quantity']))->all());
        $this->assertSame(['ar', 'إربد'], [$order->language, $order->city_name]);
    }
}
