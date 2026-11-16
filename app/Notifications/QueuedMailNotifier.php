<?php

namespace App\Notifications;

use App\Domain\Checkout\OrderNotifier;
use App\Domain\Settings\StoreSettings;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Queues the order emails, so a slow email service never delays checkout (REQ-DB-13). Customer
 * details go to the order email service only (REQ-SEC-08).
 */
class QueuedMailNotifier implements OrderNotifier
{
    public function __construct(private readonly StoreSettings $settings) {}

    public function orderPlaced(Order $order): void
    {
        $owner = $this->settings->get('order_email')
            ?: User::query()->where('role', User::OWNER)->orderBy('id')->value('email');

        if ($owner) {
            Notification::route('mail', $owner)->notify(new NewOrderForOwner($order));
        }

        if ($order->email) {
            Notification::route('mail', [$order->email => $order->customer_name])->notify(new OrderConfirmation($order));
        }
    }
}
