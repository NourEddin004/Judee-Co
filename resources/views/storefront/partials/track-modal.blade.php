{{-- "Track order" in the menu opens this; the page /track does the same without scripts. --}}
<div class="ds-modal ds-modal--sheet" data-modal="track-order" hidden>
    <div class="ds-sheet" id="track-order" role="dialog" aria-modal="true" aria-labelledby="track-order-title">
        <div class="ds-sheet__head">
            <span class="ds-sheet__icon" aria-hidden="true">{!! icon('package') !!}</span>
            <div>
                <h2 class="ds-sheet__title" id="track-order-title">{{ __('store.track.title') }}</h2>
                <p class="ds-sheet__intro">{{ __('store.track.intro') }}</p>
            </div>
            <button type="button" class="ds-icon-btn ds-sheet__close" aria-label="{{ __('store.fit.close') }}" data-modal-close>{!! icon('x') !!}</button>
        </div>
        <div class="ds-sheet__body">
            @include('storefront.partials.track-form', ['id' => 'track-number'])
            <div class="ds-track-output" aria-live="polite" data-track-output></div>
        </div>
    </div>
</div>
