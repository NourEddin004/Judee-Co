@extends('layouts.store', ['page' => 'product', 'title' => $product->t('name'), 'description' => \Illuminate\Support\Str::limit($product->t('description') ?? '', 155)])
@inject('catalog', 'App\Domain\Catalog\CatalogData')
@inject('content', 'App\Domain\Content\SiteContent')
@php
    $name = $product->t('name');
    $colors = $product->colors;
    $sizes = $product->sizes;
    $categoryUrl = category_url($category?->slug);
    $guide = $sizeGuide?->table();
    // "Find my size": only the sizes this product comes in (2026-11-20).
    $fit = collect($sizeGuide?->fitRows() ?? [])->whereIn('size', $sizes->pluck('label')->all())->values()->all();
@endphp

@section('main')
    <nav class="ds-back" aria-label="{{ __('store.product.breadcrumb') }}">
        <a href="{{ $categoryUrl }}" class="ds-back__link" data-back>
            {!! icon('chevron-left', 'ds-icon--sm') !!} {{ __('store.product.back', ['category' => $category?->t('name') ?? __('store.shop.all_title')]) }}
        </a>
    </nav>

    <section class="ds-pdp">
        <div class="ds-pdp__media" data-gallery>
            @include('storefront.partials.product-gallery', ['color' => $selected, 'live' => true])
        </div>
        @if ($colors->count() > 1)
            {{-- Each colour's photos, put in place when it is chosen; a template loads no image until then (REQ-DMO-99). --}}
            @foreach ($colors as $color)
                <template data-gallery-for="{{ $color->id }}">@include('storefront.partials.product-gallery', ['color' => $color])</template>
            @endforeach
        @endif

        <div class="ds-pdp__purchase">
            <h1 class="ds-pdp__title">{{ $name }}</h1>
            <p class="ds-pdp__price">@include('storefront.partials.price', ['amount' => $product->price])</p>
            @if ($description = $product->t('description'))
                <p class="ds-pdp__lead">{{ $description }}</p>
            @endif
            {{-- The chosen colour's name and stock; a screen reader hears them change (REQ-STR-12, REQ-DMO-100). --}}
            <p class="ds-pdp__facts" aria-live="polite" data-color-facts>
                <span>{{ __('store.product.color') }}: <strong data-color-name>{{ $selected->color?->t('name') }}</strong></span>
                <span class="ds-pdp__stock" @unless ($selected->in_stock) hidden @endunless data-stock-in>{!! icon('check', 'ds-icon--sm') !!} {{ __('store.product.in_stock') }}</span>
                <span class="ds-pdp__stock ds-pdp__stock--out" @if ($selected->in_stock) hidden @endif data-stock-out>{{ __('store.product.out_of_stock') }}</span>
            </p>
            @if ($note = $product->t('note'))
                <p class="ds-pdp__note">{{ $note }}</p>
            @endif

            <form class="ds-pdp__buy" data-buy="{{ $selected->id }}" data-name="{{ $selected->name() }}" novalidate>
                @if ($colors->count() > 1)
                    {{-- One swatch per colour, in the owner's order; choosing one changes the page in place (REQ-STR-25, D.5.8). --}}
                    <fieldset class="ds-color-picker">
                        <legend class="ds-color-picker__legend">{{ __('store.product.available_colors') }}</legend>
                        <div class="ds-color-picker__options">
                            @foreach ($colors as $color)
                                @php($colorName = $color->color?->t('name'))
                                <label class="ds-color-picker__option {{ $color->in_stock ? '' : 'is-out' }}" title="{{ $color->in_stock ? $colorName : __('store.product.color_out', ['color' => $colorName]) }}">
                                    <input class="ds-color-picker__control" type="radio" name="color" value="{{ $color->id }}" @checked($color->is($selected))
                                           data-color-slug="{{ $color->color?->slug }}" data-color-label="{{ $colorName }}" data-item-name="{{ $color->name() }}" data-in-stock="{{ $color->in_stock ? '1' : '0' }}">
                                    @include('storefront.partials.color-swatch', ['hex' => $catalog->swatchOf($color->color_id), 'class' => 'ds-color-picker__swatch'])
                                    <span class="ds-visually-hidden">{{ $color->in_stock ? $colorName : __('store.product.color_out', ['color' => $colorName]) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                @if ($sizes->isNotEmpty())
                    {{-- A sized product cannot go in the bag without a size (REQ-STR-14, REQ-DMO-30). --}}
                    <fieldset class="ds-size-picker">
                        <legend class="ds-size-picker__legend">{{ __('store.product.size') }} @if ($guide && $guide['rows'] !== []) <a href="#size-guide" class="ds-size-picker__guide">{{ __('store.product.size_guide') }}</a> @endif</legend>
                        @if ($fit !== [])
                            {{-- Optional help, never a step: the customer can pick a size without it. --}}
                            <button type="button" class="ds-fit-trigger" aria-haspopup="dialog" aria-controls="fit-finder" data-modal-open="fit-finder">
                                {!! icon('ruler', 'ds-icon--sm') !!}
                                <span>{{ __('store.fit.open') }}</span>
                                <span class="ds-fit-trigger__result" hidden data-fit-badge></span>
                            </button>
                        @endif
                        <div class="ds-size-picker__options">
                            @foreach ($sizes as $size)
                                <label class="ds-size-picker__option">
                                    <input class="ds-size-picker__control" type="radio" name="size" value="{{ $size->label }}" required data-size-input>
                                    <span class="ds-size-picker__label">{{ $size->label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="ds-size-picker__error" role="alert" hidden data-size-error>{{ __('store.product.size_required') }}</p>
                    </fieldset>
                @endif

                <div class="ds-pdp__quantity">
                    <label for="quantity">{{ __('store.product.quantity') }}</label>
                    <div class="ds-stepper" data-stepper>
                        <button type="button" class="ds-stepper__btn" aria-label="{{ __('store.product.decrease') }}" data-stepper-step="-1" disabled>{!! icon('minus') !!}</button>
                        <input id="quantity" class="ds-stepper__input" type="number" name="quantity" value="1" min="1" max="{{ config('store.max_quantity') }}" inputmode="numeric">
                        <button type="button" class="ds-stepper__btn" aria-label="{{ __('store.product.increase') }}" aria-describedby="quantity-reason" data-stepper-step="1">{!! icon('plus') !!}</button>
                    </div>
                    {{-- Plus stops at 9 and says why (REQ-DMO-55, REQ-UX-27). --}}
                    <p class="ds-disabled-reason" id="quantity-reason" hidden data-stepper-reason>{{ __('store.product.max_quantity', ['max' => config('store.max_quantity')]) }}</p>
                </div>
                {{-- "Out of stock" is already written above, so the disabled button gets no extra line (REQ-UX-27). --}}
                <button type="submit" class="ds-btn ds-btn--primary ds-btn--block" @disabled(! $selected->isPurchasable()) data-add-button>
                    {{ __('store.product.add') }} {!! icon('shopping-bag') !!}
                </button>
            </form>

            @php($perks = array_filter([$content->text('product.perk1'), $content->text('product.perk2')]))
            @if ($perks !== [])
                <ul class="ds-pdp__perks">
                    @foreach ($perks as $perk)
                        <li class="ds-pdp__perk">{!! icon('check', 'ds-icon--sm') !!} {{ $perk }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($guide && $guide['rows'] !== [])
                <section class="ds-info-card" id="size-guide" aria-labelledby="size-guide-title">
                    <h2 class="ds-info-card__title" id="size-guide-title">{{ __('store.product.size_guide') }}</h2>
                    <p class="ds-info-card__note">{{ __('store.product.size_note') }}</p>
                    <div class="ds-size-table__wrap">
                        <table class="ds-size-table">
                            <thead><tr>@foreach ($guide['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($guide['rows'] as $row)
                                    <tr><th scope="row">{{ $row[0] }}</th>@foreach (array_slice($row, 1) as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="ds-related">
            <div class="ds-section-heading">
                <h2 class="ds-section-heading__title">{{ __('store.product.related') }}</h2>
                <span class="ds-section-heading__rule"></span>
            </div>
            <div class="ds-grid ds-grid--rail">
                @include('storefront.partials.card-list', ['products' => $related])
            </div>
        </section>
    @endif
@endsection

@if ($fit !== [])
    @push('templates')
        <div class="ds-modal ds-modal--sheet" data-modal="fit-finder" hidden>
            <div class="ds-sheet" id="fit-finder" role="dialog" aria-modal="true" aria-labelledby="fit-finder-title">
                <div class="ds-sheet__head">
                    <span class="ds-sheet__icon" aria-hidden="true">{!! icon('ruler') !!}</span>
                    <div>
                        <h2 class="ds-sheet__title" id="fit-finder-title">{{ __('store.fit.title') }}</h2>
                        <p class="ds-sheet__intro">{{ __('store.fit.intro') }}</p>
                    </div>
                    <button type="button" class="ds-icon-btn ds-sheet__close" aria-label="{{ __('store.fit.close') }}" data-modal-close>{!! icon('x') !!}</button>
                </div>
                <div class="ds-sheet__body">
                    <form class="ds-fit-form" novalidate data-fit-form>
                        <script type="application/json" data-fit-rows>@json($fit)</script>
                        <div class="ds-fit-form__fields">
                            <label class="ds-fit-field">
                                <span class="ds-fit-field__label">{{ __('store.fit.height') }}</span>
                                <span class="ds-fit-field__box">
                                    <input class="ds-fit-field__input" type="number" name="height" inputmode="numeric" min="120" max="210" step="1" placeholder="160" required aria-describedby="fit-height-error" data-fit-height>
                                    <span class="ds-fit-field__unit">{{ __('store.fit.cm') }}</span>
                                </span>
                                <span class="ds-fit-field__error" id="fit-height-error" hidden data-fit-error="height"></span>
                            </label>
                            <label class="ds-fit-field">
                                <span class="ds-fit-field__label">{{ __('store.fit.weight') }}</span>
                                <span class="ds-fit-field__box">
                                    <input class="ds-fit-field__input" type="number" name="weight" inputmode="numeric" min="30" max="180" step="1" placeholder="60" required aria-describedby="fit-weight-error" data-fit-weight>
                                    <span class="ds-fit-field__unit">{{ __('store.fit.kg') }}</span>
                                </span>
                                <span class="ds-fit-field__error" id="fit-weight-error" hidden data-fit-error="weight"></span>
                            </label>
                        </div>
                        <button type="submit" class="ds-btn ds-btn--primary ds-btn--block">{{ __('store.fit.submit') }}</button>
                    </form>
                    <div class="ds-fit-result" hidden aria-live="polite" data-fit-result>
                        <p class="ds-fit-result__label">{{ __('store.fit.result') }}</p>
                        <p class="ds-fit-result__size" data-fit-size></p>
                        <p class="ds-fit-result__text" data-fit-text></p>
                        <button type="button" class="ds-btn ds-btn--secondary ds-btn--block" data-fit-choose></button>
                    </div>
                    <p class="ds-fit-note">{{ __('store.fit.note') }}</p>
                </div>
            </div>
        </div>
    @endpush
@endif
