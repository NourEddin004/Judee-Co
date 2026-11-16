@extends('layouts.store', ['page' => 'home', 'title' => null])
@inject('content', 'App\Domain\Content\SiteContent')

@push('preload')
    {{-- The headline is the largest text on a phone: its font loads with the stylesheet, not after it (REQ-PRF-02). --}}
    <link rel="preload" href="/fonts/{{ app()->getLocale() === 'ar' ? 'ibm-plex-sans-arabic-arabic-400-normal' : 'bodoni-moda-latin-400-normal' }}.woff2" as="font" type="font/woff2" crossorigin>
@endpush

@section('main')
    {{-- 1. Hero with the clothes rack (D.6.1) --}}
    <section class="ds-hero">
        <div class="ds-hero__content">
            <h1 class="ds-hero__headline"><span class="ds-hero__headline-main">{{ $content->text('home.hero.title') }}</span></h1>
            @if ($body = $content->text('home.hero.body'))
                <p class="ds-hero__lead">{{ $body }}</p>
            @endif
            <div class="ds-hero__actions">
                <a href="{{ store_route('shop') }}" class="ds-btn ds-btn--primary ds-btn--pill">{{ $content->text('home.hero.cta') }}</a>
            </div>
        </div>
        @include('storefront.partials.rack', ['rack' => $rack, 'label' => $content->text('home.hero.rack_label')])
    </section>

    {{-- 2. Trust strip --}}
    <section class="ds-trust" aria-label="{{ __('store.home.store_info') }}">
        @foreach (__('store.home.trust') as $item)
            <article class="ds-trust__item">
                {!! icon($item['icon'], 'ds-icon--lg ds-icon--light') !!}
                <div>
                    <h2 class="ds-trust__title">{{ $item['title'] }}</h2>
                    <p class="ds-trust__text">{{ $item['text'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    {{-- 3. About the brand --}}
    <section class="ds-callout">
        <div class="ds-callout__looks">
            @foreach ($looks as $look)
                @php($photo = $look->photos->get(1) ?? $look->photos->first())
                <a class="ds-callout__look" href="{{ $look->url() }}">
                    <span class="ds-callout__look-media">
                        <img class="ds-callout__look-image" src="{{ $photo?->url() }}" srcset="{{ srcset($photo?->path) }}" sizes="(min-width: 48rem) 15vw, 33vw" alt="{{ $look->name() }}" loading="lazy">
                    </span>
                    <span class="ds-callout__look-caption">{{ $look->name() }}</span>
                </a>
            @endforeach
        </div>
        <div class="ds-callout__copy">
            <p class="ds-eyebrow">{{ $content->text('home.about.kicker') }}</p>
            <h2 class="ds-callout__title">{{ $content->text('home.about.title') }}</h2>
            <p class="ds-callout__body">{{ $content->text('home.about.body') }}</p>
            <a href="{{ store_route('shop') }}" class="ds-btn ds-btn--primary ds-btn--pill">{{ $content->text('home.about.cta') }}</a>
        </div>
    </section>

    {{-- 4. How to order --}}
    <section class="ds-stores" id="order" aria-labelledby="order-title">
        <div class="ds-stores__intro">
            <h2 class="ds-stores__title" id="order-title">
                {{ $content->text('home.order.title') }}
                @if ($note = $content->text('home.order.note'))
                    <span class="ds-stores__title-note">{{ $note }}</span>
                @endif
            </h2>
        </div>
        <ol class="ds-order-grid">
            @foreach (range(1, 4) as $step)
                <li class="ds-order">
                    <span class="ds-order__title">{{ $content->text("home.order.step{$step}_title") }}</span>
                    <span class="ds-order__detail">{{ $content->text("home.order.step{$step}_text") }}</span>
                    @if ($step < 4)
                        <span class="ds-order__arrow" aria-hidden="true">{!! icon('arrow-right') !!}</span>
                    @endif
                </li>
            @endforeach
        </ol>
        <div class="ds-order-cta">
            <a href="{{ store_route('shop') }}" class="ds-btn ds-btn--primary ds-btn--pill">{{ $content->text('home.order.cta') }}</a>
        </div>
    </section>

    {{-- 5. Client reviews, only when one is published (REQ-DMO-81) --}}
    @if ($reviews->isNotEmpty())
        <section class="ds-testimonials" id="reviews" aria-labelledby="reviews-title">
            <div class="ds-testimonials__header">
                <h2 class="ds-testimonials__title" id="reviews-title">{{ $content->text('home.reviews.title') }}</h2>
            </div>
            <div class="ds-testimonials__grid">
                @foreach ($reviews as $review)
                    <figure class="ds-testimonial">
                        @if ($review->image_path)
                            <img class="ds-testimonial__image" src="{{ Storage::disk('public')->url($review->image_path) }}" srcset="{{ srcset($review->image_path) }}" sizes="(min-width: 48rem) 33vw, 100vw" alt="{{ $review->t('body') }}" loading="lazy">
                        @endif
                        <figcaption class="ds-testimonial__footer">
                            <span class="ds-testimonial__name">
                                {{ $review->author }}
                                @if ($review->country_code)
                                    <span class="ds-testimonial__flag" role="img" aria-label="{{ __('store.countries.'.$review->country_code) }}" title="{{ __('store.countries.'.$review->country_code) }}">{{ \App\Support\Flag::emoji($review->country_code) }}</span>
                                @endif
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif
@endsection
