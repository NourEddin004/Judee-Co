{!! __('store.email.greeting', ['name' => $order->customer_name]) !!}

{!! __('store.email.intro', ['number' => $order->number]) !!}
{!! __('store.confirmation.keep_number') !!}

@foreach ($order->items as $item)
{!! $item->product_name !!}{!! $item->size ? ' ('.__('store.checkout.size', ['size' => $item->size]).')' : '' !!} {!! __('store.checkout.qty', ['qty' => $item->quantity]) !!}    {!! $item->lineTotal()->format() !!}
@endforeach

{!! __('store.checkout.subtotal') !!}: {!! \App\Support\Money::jod($order->subtotal) !!}
{!! __('store.checkout.delivery') !!}: {!! \App\Support\Money::jod($order->delivery_fee) !!}
{!! __('store.checkout.total') !!}: {!! \App\Support\Money::jod($order->total) !!}

{!! __('store.confirmation.deliver_to') !!}: {!! $order->customer_name !!}{!! $order->comma() !!}{!! $order->deliveryAddress() !!}
{!! __('store.confirmation.phone') !!}: {!! $order->phone !!}
{!! __('store.confirmation.payment') !!}: {!! __('store.checkout.payment_method') !!}

{!! __('store.email.sign_off') !!}
{!! __('store.email.signature') !!}
