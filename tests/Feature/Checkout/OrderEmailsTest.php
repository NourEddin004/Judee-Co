<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/**
 * The two order emails as they are really sent (the test queue runs at once and keeps the mail):
 * wording (REQ-DMO-77), language (REQ-CHK-20) and the owner's copy (REQ-CHK-18).
 */
class OrderEmailsTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['store.free_delivery_from' => null]); // the fee lines below are the flat city fee
        RateLimiter::clear('orders:127.0.0.1');
        Setting::query()->updateOrCreate(['key' => 'order_email'], ['value' => 'orders@judee.co']);
    }

    private function place(string $url = '/checkout', string $area = 'Khalda'): void
    {
        $this->postJson($url, [
            'full_name' => 'Lina Khalil', 'country' => 'JO', 'phone' => '0791234567', 'email' => 'lina@example.com',
            'city' => 'amman', 'area' => $area, 'address' => 'Wasfi Al-Tal St, building 12', 'note' => '',
            'lines' => [['id' => $this->piece('black-wrap-set')->id, 'size' => 'M', 'qty' => 1], ['id' => $this->piece('rosemary-hijab')->id, 'size' => null, 'qty' => 2]],
        ])->assertCreated();
    }

    /** @return array<string, Email> sent emails by address */
    private function sent(): array
    {
        $emails = [];
        foreach (app('mailer')->getSymfonyTransport()->messages() as $message) {
            $email = $message->getOriginalMessage();
            $emails[$email->getTo()[0]->getAddress()] = $email;
        }

        return $emails;
    }

    #[Test]
    public function req_dmo_76_req_dmo_77_the_customer_email_is_worded_as_in_the_demo(): void
    {
        $this->place();

        $email = $this->sent()['lina@example.com'];
        $number = Order::query()->value('number');
        $text = $email->getTextBody();

        $this->assertSame("Your JUDE order #{$number}", $email->getSubject());
        $this->assertStringStartsWith('Hi Lina Khalil,', $text);
        $this->assertStringContainsString("Thank you for your order! Here are the details of order #{$number}:", $text);
        foreach (['Black Wrap Set', 'Rosemary Hijab', 'Subtotal: 69 JD', 'Delivery: 3 JD', 'Total: 72 JD', 'Deliver to: Lina Khalil, Wasfi Al-Tal St, building 12, Khalda, Amman', 'Phone: +962 791234567', 'Payment: Cash on delivery'] as $part) {
            $this->assertStringContainsString($part, $text);
        }
        $this->assertStringEndsWith("With love,\nJUDE", trim($text));
        $this->assertStringContainsString('Black Wrap Set', (string) $email->getHtmlBody());
    }

    #[Test]
    public function req_chk_20_an_arabic_customer_gets_the_email_in_arabic(): void
    {
        $this->place('ar:/checkout', 'خلدا');

        $email = $this->sent()['lina@example.com'];
        $number = Order::query()->value('number');

        $this->assertSame(__('store.email.subject', ['number' => $number], 'ar'), $email->getSubject());
        $this->assertStringContainsString(__('store.email.greeting', ['name' => 'Lina Khalil'], 'ar'), $email->getTextBody());
        $this->assertStringContainsString('حجاب روزماري', $email->getTextBody());
        $this->assertStringContainsString('Lina Khalil، Wasfi Al-Tal St, building 12، خلدا، عمّان', $email->getTextBody());   // Arabic commas between the parts
        $this->assertStringContainsString('dir="rtl"', (string) $email->getHtmlBody());
    }

    #[Test]
    public function req_chk_18_the_owner_gets_every_detail_and_a_link_to_the_order(): void
    {
        $this->place();

        $email = $this->sent()['orders@judee.co'];
        $order = Order::query()->firstOrFail();

        $this->assertSame("New order #{$order->number}", $email->getSubject());
        foreach (['Lina Khalil', '+962 791234567', 'lina@example.com', 'Khalda, Amman', 'Black Wrap Set (Size M) × 1', 'Total: 72 JD', url('/admin/orders/'.$order->id)] as $part) {
            $this->assertStringContainsString($part, $email->getTextBody());
        }
        // The private confirmation link is never put in an email to the owner.
        $this->assertStringNotContainsString($order->link_token, $email->getTextBody().$email->getHtmlBody());
    }
}
