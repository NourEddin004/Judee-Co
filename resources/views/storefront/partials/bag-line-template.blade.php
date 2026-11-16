@once
@push('templates')
{{-- One bag line; the script clones it for each line (D.5.9). --}}
<template id="bag-line-template">
    <article class="ds-cart-line" data-bag-line>
        <img class="ds-cart-line__image" src="" alt="" loading="lazy" data-line-image>
        <div class="ds-cart-line__copy">
            <a href="" class="ds-cart-line__name" data-line-name></a>
            <span class="ds-cart-line__meta" data-line-size hidden></span>
        </div>
        <div class="ds-stepper ds-stepper--compact ds-cart-line__qty">
            <button type="button" class="ds-stepper__btn" data-line-step="-1">{!! icon('minus') !!}</button>
            <span class="ds-stepper__value" data-line-qty></span>
            <button type="button" class="ds-stepper__btn" data-line-step="1">{!! icon('plus') !!}</button>
        </div>
        <p class="ds-disabled-reason ds-cart-line__reason" hidden data-line-reason></p>
        <p class="ds-cart-line__total" data-line-total></p>
        <div class="ds-cart-line__remove">
            <button type="button" class="ds-icon-btn" data-line-remove>{!! icon('trash') !!}</button>
        </div>
    </article>
</template>
@endpush
@endonce
