{{-- Product card (D.5.6): one per product, showing one of its colours, with a dot for each colour when it has 2 or more (REQ-STR-24). --}}
@inject('catalog', 'App\Domain\Catalog\CatalogData')
@php
    $name = $product->t('name');
    $shown = $product->shownColor;
    $colors = $product->colors;
    $several = $colors->count() > 1;
    // The card opens the product page on the colour it shows (D.5.6); the first colour needs no ?color=.
    $url = product_url($product->slug, $several && $shown && ! $shown->is($colors->first()) ? $catalog->colors()->get($shown->color_id)?->slug : null);
    $needsSize = (bool) $product->needs_size;
    $image = $shown?->cardPhoto();
    $hover = $shown?->cardHoverPhoto();
    $sizes = '(min-width: 64rem) 25vw, (min-width: 48rem) 33vw, 50vw';
    $badge = $product->t('badge');
    $maxDots = 5;
@endphp
<article class="ds-card {{ $needsSize ? 'ds-card--fit' : '' }}">
    <div class="ds-card__media">
        <a href="{{ $url }}" class="ds-card__image-link" aria-label="{{ __('store.card.view', ['product' => $name]) }}">
            <img class="ds-card__image {{ $hover ? 'ds-card__image--primary' : '' }}" src="{{ $image?->url() }}" srcset="{{ srcset($image?->path) }}" sizes="{{ $sizes }}" alt="{{ __('store.card.look', ['product' => $name]) }}"
                 @if ($eager ?? false) @if ($first ?? false) fetchpriority="high" @endif @else loading="lazy" @endif>
            @if ($hover)
                <img class="ds-card__image ds-card__image--secondary" src="{{ $hover->url() }}" srcset="{{ srcset($hover->path) }}" sizes="{{ $sizes }}" alt="" loading="lazy">
            @endif
        </a>
        @if ($badge)
            <span class="ds-badge ds-card__badge">{{ $badge }}</span>
        @endif
    </div>
    <div class="ds-card__body">
        <div class="ds-card__summary">
            <div class="ds-card__heading">
                <a href="{{ $url }}" class="ds-card__title">{{ $name }}</a>
                <p class="ds-card__price">@include('storefront.partials.price', ['amount' => $product->price])</p>
            </div>
            @if ($several)
                {{-- The dots only show which colours there are; the colour is chosen on the product page (REQ-STR-24). --}}
                <p class="ds-card__colors" role="img" aria-label="{{ trans_choice('store.card.colors', $colors->count(), ['count' => $colors->count()]) }}">
                    @foreach ($colors->take($maxDots) as $color)
                        @include('storefront.partials.color-swatch', ['hex' => $catalog->swatchOf($color->color_id), 'class' => 'ds-card__dot'])
                    @endforeach
                    @if ($colors->count() > $maxDots)
                        <span class="ds-card__more">{{ __('store.card.more_colors', ['count' => $colors->count() - $maxDots]) }}</span>
                    @endif
                </p>
            @endif
        </div>
        <div class="ds-card__actions">
            @if ($needsSize)
                <a href="{{ $url }}" class="ds-btn ds-btn--secondary ds-btn--block ds-btn--compact" aria-label="{{ __('store.card.choose_size_label', ['product' => $name]) }}">{{ __('store.card.choose_size') }}</a>
            @elseif ($several)
                <a href="{{ $url }}" class="ds-btn ds-btn--secondary ds-btn--block ds-btn--compact" aria-label="{{ __('store.card.choose_color_label', ['product' => $name]) }}">{{ __('store.card.choose_color') }}</a>
            @else
                {{-- A sold-out card's button says why in its own label (REQ-UX-27, REQ-CHK-16). --}}
                @php($soldOut = $shown === null || ! $shown->isPurchasable())
                <button type="button" class="ds-btn ds-btn--primary ds-btn--block ds-btn--compact" @if ($shown) data-add-to-bag="{{ $shown->id }}" @endif data-name="{{ $name }}"
                        @disabled($soldOut)
                        @unless ($soldOut) aria-label="{{ __('store.card.add_label', ['product' => $name]) }}" @endunless>
                    {{ $soldOut ? __('store.card.out_of_stock') : __('store.card.add') }}
                </button>
            @endif
        </div>
    </div>
</article>
