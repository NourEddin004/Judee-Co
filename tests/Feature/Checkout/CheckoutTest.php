<?php

namespace Tests\Feature\Checkout;

use App\Domain\Orders\OrderNumber;
use App\Domain\Orders\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewOrderForOwner;
use App\Notifications\OrderConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/** Placing an order on the server (SRS 3.3, C.4, C.6, C.11). */
class CheckoutTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        RateLimiter::clear('orders:127.0.0.1');
    }

    /** The checkout form as the browser sends it: one Black Wrap Set in M and two Rosemary Hijabs, to Khalda, Amman. */
    private function form(array $changes = []): array
    {
        return array_replace([
            'full_name' => 'Lina Khalil',
            'country' => 'JO',
            'phone' => '079 123 4567',
            'email' => 'lina@example.com',
            'city' => 'amman',
            'area' => 'Khalda',
            'area_other' => '',
            'address' => 'Wasfi Al-Tal St, building 12',
            'note' => 'Please call before delivery',
            'website' => '',
            'lines' => [
                ['id' => $this->piece('black-wrap-set')->id, 'size' => 'M', 'qty' => 1],
                ['id' => $this->piece('rosemary-hijab')->id, 'size' => null, 'qty' => 2],
            ],
        ], $changes);
    }

    private function place(array $changes = [], string $url = '/checkout', array $headers = []): TestResponse
    {
        return $this->postJson($url, $this->form($changes), $headers + ['Origin' => config('app.url'), 'Sec-Fetch-Site' => 'same-origin']);
    }

    private function product(string $slug): Product
    {
        return Product::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();
    }

    private function pause(): void
    {
        Setting::query()->updateOrCreate(['key' => 'store_paused'], ['value' => '1']);
    }

    private function onlyOrder(): Order
    {
        $this->assertSame(1, Order::query()->count());

        return Order::query()->with('items')->firstOrFail();
    }

    #[Test]
    public function req_chk_14_req_db_06_the_order_and_all_its_items_are_saved(): void
    {
        $this->place()->assertCreated();

        $order = $this->onlyOrder();
        $this->assertSame('Lina Khalil', $order->customer_name);
        $this->assertSame('+962 791234567', $order->phone);
        $this->assertSame('lina@example.com', $order->email);
        $this->assertSame('Amman', $order->city_name);
        $this->assertSame('Khalda', $order->area);
        $this->assertSame('Wasfi Al-Tal St, building 12', $order->address);
        $this->assertSame('Please call before delivery', $order->note);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('cash_on_delivery', $order->payment_method);
        $this->assertCount(2, $order->items);
    }

    #[Test]
    public function req_db_07_each_item_keeps_a_copy_of_name_color_size_and_price(): void
    {
        $this->place()->assertCreated();

        $items = $this->onlyOrder()->items->sortBy('unit_price')->values();
        $this->assertSame(['Rosemary Hijab', 'Rosemary', null, '17.00', 2], [$items[0]->product_name, $items[0]->color_name, $items[0]->size, $items[0]->unit_price, $items[0]->quantity]);
        $this->assertSame(['Black Wrap Set', 'Black', 'M', '35.00', 1], [$items[1]->product_name, $items[1]->color_name, $items[1]->size, $items[1]->unit_price, $items[1]->quantity]);
    }

    #[Test]
    public function req_chk_10_req_chk_12_req_chk_13_req_dmo_45_prices_and_fee_come_from_the_database_in_jod(): void
    {
        // Prices or a fee sent by the browser are ignored (REQ-SEC-25).
        $this->place(['subtotal' => '1', 'delivery_fee' => '0', 'total' => '1', 'price' => '1'])->assertCreated();

        $order = $this->onlyOrder();
        $this->assertSame('69.00', $order->subtotal);      // 35 + 2 × 17
        $this->assertSame('3.00', $order->delivery_fee);   // Amman, 3 JD like every Jordan city (REQ-CHK-26)
        $this->assertSame('72.00', $order->total);
    }

    #[Test]
    public function req_chk_13_a_city_outside_amman_gets_its_own_fee(): void
    {
        $this->place(['city' => 'irbid', 'area' => '__other', 'area_other' => 'Al-Hashmi'])->assertCreated();

        $order = $this->onlyOrder();
        $this->assertSame(['Irbid', 'Al-Hashmi', '3.00', '72.00'], [$order->city_name, $order->area, $order->delivery_fee, $order->total]);
    }

    #[Test]
    public function req_chk_07_req_chk_09_req_db_12_required_fields_are_checked_before_anything_is_saved(): void
    {
        $this->place(['full_name' => '', 'phone' => '', 'city' => '', 'area' => '', 'address' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name', 'phone', 'city', 'area', 'address']);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_chk_09_wrong_formats_are_refused_with_the_demo_messages(): void
    {
        $this->place(['phone' => '12', 'email' => 'lina@', 'city' => 'atlantis'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'phone' => 'Please enter 9 digits after +962.',                                // REQ-DMO-70
                'email' => 'Please enter a valid email address.',                              // REQ-DMO-71
                'city' => __('store.checkout.invalid_city'),
            ]);

        $this->place(['area' => 'Not a real area'])->assertJsonValidationErrors(['area']);
        $this->place(['area' => '__other', 'area_other' => ''])->assertJsonValidationErrors(['area_other']);
        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_chk_08_email_is_optional(): void
    {
        $this->place(['email' => ''])->assertCreated();

        $this->assertNull($this->onlyOrder()->email);
    }

    #[Test]
    public function req_dmo_57_a_phone_typed_with_its_dial_code_is_saved_once_with_it(): void
    {
        $this->place(['phone' => '+962 79 123 4567'])->assertCreated();

        $this->assertSame('+962 791234567', $this->onlyOrder()->phone);
    }

    #[Test]
    public function req_chk_22_req_dmo_56_the_phone_needs_exactly_the_digits_of_the_chosen_country(): void
    {
        $this->place(['phone' => '0791234567'])->assertCreated();
        $this->place(['phone' => '791234567'])->assertCreated();
        $this->assertSame(['+962 791234567', '+962 791234567'], Order::query()->pluck('phone')->all());

        $this->place(['phone' => '79123456'])->assertJsonValidationErrors(['phone' => 'Please enter 9 digits after +962.']);
        $this->place(['country' => 'KW', 'phone' => '500123456'])->assertJsonValidationErrors(['phone' => 'Please enter 8 digits after +965.']);
        $this->place(['country' => 'LB', 'phone' => '711234567'])->assertJsonValidationErrors(['phone' => 'Please enter 7 or 8 digits after +961.']);
        $this->place(['country' => 'DE', 'phone' => '151234567'])->assertJsonValidationErrors(['phone' => 'Please enter 10 or 11 digits after +49.']);
        $this->assertSame(2, Order::query()->count());

        $this->place(['country' => 'KW', 'phone' => '50012345'])->assertCreated();
        $this->assertSame('+965 50012345', Order::query()->latest('id')->value('phone'));
    }

    #[Test]
    public function req_dmo_70_req_dmo_90_the_arabic_checkout_takes_arabic_digits_and_answers_in_arabic(): void
    {
        $this->place(['phone' => '٠٧٩١٢٣٤٥٦٧', 'area' => 'خلدا'], 'ar:/checkout')->assertCreated();
        $this->assertSame('+962 791234567', $this->onlyOrder()->phone);

        $this->place(['phone' => '٧٩١٢'], 'ar:/checkout')
            ->assertJsonValidationErrors(['phone' => "يرجى إدخال 9 أرقام بعد \u{2066}+962\u{2069}."]);
    }

    #[Test]
    public function an_area_may_be_sent_in_arabic_and_the_order_keeps_the_customers_language(): void
    {
        $this->place(['area' => 'خلدا'], 'ar:/checkout')->assertCreated();

        $order = $this->onlyOrder();
        $this->assertSame(['ar', 'عمّان', 'خلدا'], [$order->language, $order->city_name, $order->area]);
        $this->assertContains('حجاب روزماري', $order->items->pluck('product_name')->all());
    }

    #[Test]
    public function req_chk_16_out_of_stock_products_are_not_orderable(): void
    {
        $this->piece('rosemary-hijab')->update(['in_stock' => false]);

        $this->place()
            ->assertUnprocessable()
            ->assertJson([
                'reason' => 'unavailable',
                'ids' => [$this->piece('rosemary-hijab')->id],
                'message' => 'Rosemary Hijab is no longer available, so it was taken out of your bag. Please check your order and place it again.',
            ]);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_is_refused(): void
    {
        $navy = $this->piece('navy-wrap-set');
        $navy->update(['in_stock' => false]);

        // The same product in another colour is still orderable.
        $this->place(['lines' => [['id' => $this->piece('black-wrap-set')->id, 'size' => 'M', 'qty' => 1]]])->assertCreated();

        $this->place(['lines' => [['id' => $navy->id, 'size' => 'M', 'qty' => 1]]])
            ->assertUnprocessable()
            ->assertJson(['reason' => 'unavailable', 'ids' => [$navy->id], 'message' => 'Navy Wrap Set is no longer available, so it was taken out of your bag. Please check your order and place it again.']);

        $this->assertSame(['Black Wrap Set'], $this->onlyOrder()->items->pluck('product_name')->all());
    }

    #[Test]
    public function req_db_31_hidden_products_and_unknown_sizes_are_refused(): void
    {
        $this->product('rosemary-hijab')->update(['is_published' => false]);
        $this->place()->assertUnprocessable()->assertJsonValidationErrors(['lines' => __('store.checkout.bag_changed')]);

        $this->product('rosemary-hijab')->update(['is_published' => true]);
        $this->place(['lines' => [['id' => $this->piece('black-wrap-set')->id, 'size' => 'XXXL', 'qty' => 1]]])
            ->assertJsonValidationErrors(['lines']);

        // A sized product needs its size.
        $this->place(['lines' => [['id' => $this->piece('black-wrap-set')->id, 'size' => null, 'qty' => 1]]])
            ->assertUnprocessable()->assertJson(['reason' => 'unavailable']);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_dmo_27_req_dmo_28_lines_of_the_same_piece_are_added_together_up_to_9(): void
    {
        $id = $this->piece('rosemary-hijab')->id;

        $this->place(['lines' => [['id' => $id, 'size' => null, 'qty' => 6], ['id' => $id, 'size' => null, 'qty' => 6]]])->assertCreated();

        $order = $this->onlyOrder();
        $this->assertCount(1, $order->items);
        $this->assertSame(9, $order->items->first()->quantity);
        $this->assertSame('153.00', $order->subtotal);

        $this->place(['lines' => [['id' => $id, 'size' => null, 'qty' => 10]]])->assertJsonValidationErrors(['lines.0.qty']);
        $this->place(['lines' => []])->assertJsonValidationErrors(['lines']);
    }

    #[Test]
    public function req_chk_17_while_the_store_is_paused_checkout_takes_no_orders(): void
    {
        $this->pause();

        $this->place()->assertUnprocessable()->assertJson(['reason' => 'paused', 'message' => __('store.paused.reason')]);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_chk_17_req_ux_27_a_paused_store_disables_place_order_and_says_why_below_it(): void
    {
        $this->get('/checkout')->assertOk()->assertDontSee('id="place-order-reason" hidden', false);
        $this->pause();

        $html = $this->get('/checkout')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*data-place-order[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/<p class="ds-disabled-reason" id="place-order-reason"\s+data-place-order-reason>Orders are paused for now\. Please check back soon\.<\/p>/', $html);
    }

    #[Test]
    public function req_chk_15_req_dmo_73_req_db_10_each_order_gets_a_unique_6_character_number_from_the_server(): void
    {
        $this->place()->assertCreated();
        $this->place()->assertCreated();

        $numbers = Order::query()->pluck('number');
        $this->assertCount(2, $numbers->unique());
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^['.OrderNumber::ALPHABET.']{6}$/', $number);
        }
    }

    #[Test]
    public function req_db_10_a_number_that_is_already_taken_is_never_given_twice(): void
    {
        Order::factory()->create(['number' => 'AAAAAA']);
        $this->place()->assertCreated();

        $this->assertSame(1, Order::query()->where('number', 'AAAAAA')->count());
    }

    #[Test]
    public function req_chk_15_req_dmo_75_req_sec_36_the_reply_is_only_the_confirmation_address(): void
    {
        $response = $this->place()->assertCreated();

        $order = $this->onlyOrder();
        $response->assertExactJson(['redirect' => url('/order/'.$order->link_token)]);

        $this->get('/order/'.$order->link_token)
            ->assertOk()
            ->assertSee('Order #'.$order->number)
            ->assertSee('Black Wrap Set')
            ->assertSee('72 JD')
            ->assertSee('Wasfi Al-Tal St, building 12, Khalda, Amman')
            ->assertSee('+962 791234567')
            ->assertSee('noindex, nofollow', false);
    }

    #[Test]
    public function req_sec_41_req_db_32_the_confirmation_page_opens_only_with_its_random_token(): void
    {
        $this->place()->assertCreated();
        $order = $this->onlyOrder();

        $this->assertGreaterThanOrEqual(32, strlen($order->link_token));
        $this->get('/order/'.$order->number)->assertRedirect('/shop');
        $this->get('/order/'.$order->id)->assertRedirect('/shop');
        $this->get('/order/'.substr($order->link_token, 0, 63).'x')->assertRedirect('/shop');
        $this->get('/order')->assertRedirect('/shop');
    }

    #[Test]
    public function req_chk_18_req_chk_19_req_db_13_the_owner_and_the_customer_are_emailed_through_the_queue(): void
    {
        Setting::query()->updateOrCreate(['key' => 'order_email'], ['value' => 'orders@judee.co']);

        $this->place()->assertCreated();
        $order = $this->onlyOrder();

        Notification::assertSentOnDemand(NewOrderForOwner::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'orders@judee.co' && $notification->order->is($order));
        Notification::assertSentOnDemand(OrderConfirmation::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === ['lina@example.com' => 'Lina Khalil']);
        $this->assertInstanceOf(ShouldQueue::class, new OrderConfirmation($order));
        $this->assertInstanceOf(ShouldQueue::class, new NewOrderForOwner($order));
    }

    #[Test]
    public function req_chk_18_without_an_order_email_setting_the_owners_account_gets_the_email(): void
    {
        Setting::query()->where('key', 'order_email')->delete();
        $owner = User::factory()->owner()->create(['email' => 'jude@judee.co']);
        User::query()->where('role', 'owner')->whereKeyNot($owner->id)->delete();

        $this->place()->assertCreated();

        Notification::assertSentOnDemand(NewOrderForOwner::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'jude@judee.co');
    }

    #[Test]
    public function req_chk_19_no_customer_email_without_an_address(): void
    {
        Setting::query()->updateOrCreate(['key' => 'order_email'], ['value' => 'orders@judee.co']);

        $this->place(['email' => ''])->assertCreated();

        Notification::assertSentOnDemandTimes(OrderConfirmation::class, 0);
        Notification::assertSentOnDemandTimes(NewOrderForOwner::class, 1);
    }

    #[Test]
    public function req_db_14_a_failed_email_is_tried_again_at_least_3_times(): void
    {
        $this->place()->assertCreated();
        $order = $this->onlyOrder();

        foreach ([new OrderConfirmation($order), new NewOrderForOwner($order)] as $email) {
            $this->assertGreaterThanOrEqual(4, $email->tries);
            $this->assertCount(3, $email->backoff);
        }
    }

    #[Test]
    public function req_sec_06_a_filled_anti_spam_field_is_refused(): void
    {
        $this->place(['website' => 'http://spam.example'])->assertUnprocessable();

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_sec_24_at_most_5_orders_an_hour_from_one_address(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->place()->assertCreated();
        }

        $this->place()->assertStatus(429)->assertJson(['message' => __('store.checkout.too_many')]);
        $this->assertSame(5, Order::query()->count());
    }

    #[Test]
    public function req_sec_24_refused_orders_do_not_count_towards_the_limit(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->place(['full_name' => ''])->assertUnprocessable();
        }

        $this->place()->assertCreated();
    }

    #[Test]
    public function req_sec_40_orders_from_another_site_are_refused(): void
    {
        $this->place([], '/checkout', ['Origin' => 'https://evil.example'])->assertForbidden();
        $this->place([], '/checkout', ['Sec-Fetch-Site' => 'cross-site'])->assertForbidden();

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_sec_25_req_sec_35_fields_that_are_not_on_the_form_are_ignored(): void
    {
        $this->place(['status' => 'delivered', 'number' => 'HACKED', 'link_token' => str_repeat('a', 64), 'language' => 'xx'])->assertCreated();

        $order = $this->onlyOrder();
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertNotSame('HACKED', $order->number);
        $this->assertNotSame(str_repeat('a', 64), $order->link_token);
        $this->assertSame('en', $order->language);
    }

    #[Test]
    public function req_ord_12_a_note_longer_than_500_characters_is_refused(): void
    {
        $this->place(['note' => str_repeat('a', 501)])->assertJsonValidationErrors(['note' => 'Please keep your note under 500 characters.']);
        $this->place(['note' => ''])->assertCreated();

        $this->assertNull($this->onlyOrder()->note);
    }

    #[Test]
    public function req_sec_35_bytes_that_are_not_text_never_break_the_checkout(): void
    {
        $response = $this->post('/checkout', $this->form(['full_name' => "Lina \xFF\xFE", 'address' => "Street \xC3\x28", 'note' => "\xFF"]),
            ['Origin' => config('app.url'), 'Sec-Fetch-Site' => 'same-origin']);

        $this->assertLessThan(500, $response->getStatusCode());
        foreach (Order::query()->get() as $order) {
            $this->assertTrue(mb_check_encoding($order->customer_name.$order->address.$order->note, 'UTF-8'));
        }
    }
}
