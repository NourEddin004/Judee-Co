{{-- Each piece with its size, quantity and price, then the delivery fee and the total (REQ-ORD-13 to 15). --}}
@php($order = $getRecord())
<div class="jude-order-lines">
    @foreach ($order->items as $item)
        <div class="jude-order-line">
            @if ($photo = $item->productColor?->mainPhoto)
                <img class="jude-order-line__thumb" src="{{ $photo->smallUrl() }}" alt="" loading="lazy" width="48" height="60">
            @else
                <span class="jude-order-line__thumb" aria-hidden="true"></span>
            @endif
            <div class="jude-order-line__text">
                <div class="jude-order-line__name">{{ $item->product_name }}</div>
                <div class="jude-order-line__meta">
                    {{ implode(' · ', array_filter([$item->color_name, $item->size ? __('Size :size', ['size' => $item->size]) : null, __('Qty :qty', ['qty' => $item->quantity])])) }}
                    @if ($item->quantity > 1)
                        · {{ \App\Support\Money::jod($item->unit_price) }} each
                    @endif
                </div>
            </div>
            <span class="jude-order-line__money">{{ \App\Support\Money::jod($item->lineTotal()->toDecimal()) }}</span>
        </div>
    @endforeach
    <div class="jude-order-line jude-order-line--sum">
        <div class="jude-order-line__text jude-order-line__meta">{{ __('Subtotal') }}</div>
        <span class="jude-order-line__money">{{ \App\Support\Money::jod($order->subtotal) }}</span>
    </div>
    <div class="jude-order-line jude-order-line--sum">
        <div class="jude-order-line__text jude-order-line__meta">{{ __('Delivery to :city', ['city' => $order->city_name]) }}</div>
        <span class="jude-order-line__money">{{ \App\Support\Money::jod($order->delivery_fee) }}</span>
    </div>
    <div class="jude-order-line jude-order-line--total">
        <div class="jude-order-line__text"><strong>{{ __('Total') }}</strong></div>
        <span class="jude-order-line__money">{{ \App\Support\Money::jod($order->total) }}</span>
    </div>
</div>
