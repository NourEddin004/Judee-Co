New order #{!! $order->number !!}
Placed {!! $order->placed_at->timezone('Asia/Amman')->format('j M Y, H:i') !!} (Jordan time). Call the customer to confirm it.

Customer: {!! $order->customer_name !!}
Phone: {!! $order->phone !!}
@if ($order->email)
Email: {!! $order->email !!}
@endif
Deliver to: {!! $order->deliveryAddress() !!}
@if ($order->note)
Note: {!! $order->note !!}
@endif
Payment: Cash on delivery
Language: {!! $order->language === 'ar' ? 'Arabic' : 'English' !!}

@foreach ($order->items as $item)
{!! $item->product_name !!}{!! $item->size ? ' (Size '.$item->size.')' : '' !!} × {!! $item->quantity !!}    {!! $item->lineTotal()->format() !!}
@endforeach

Subtotal: {!! \App\Support\Money::jod($order->subtotal) !!}
Delivery: {!! \App\Support\Money::fee($order->delivery_fee) !!}
Total: {!! \App\Support\Money::jod($order->total) !!}

Open the order: {!! $adminUrl !!}
