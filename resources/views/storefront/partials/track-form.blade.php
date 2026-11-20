{{-- The order-number form, in the menu's popup and on the /track page; it works without scripts too. --}}
<form class="ds-track-form" action="{{ store_route('track') }}" method="get" novalidate data-track-form>
    <label class="ds-track-form__label" for="{{ $id }}">{{ __('store.track.label') }}</label>
    <div class="ds-track-form__row">
        <input id="{{ $id }}" class="ds-input__field ds-track-form__input" type="text" name="number" value="{{ $typed ?? '' }}"
               placeholder="{{ __('store.track.placeholder') }}" maxlength="10" autocomplete="off" autocapitalize="characters" spellcheck="false"
               dir="ltr" aria-describedby="{{ $id }}-hint {{ $id }}-error" @if (! empty($error)) aria-invalid="true" @endif required data-track-input>
        <button type="submit" class="ds-btn ds-btn--primary ds-track-form__submit" data-track-submit>{{ __('store.track.submit') }}</button>
    </div>
    <p class="ds-track-form__hint" id="{{ $id }}-hint">{{ __('store.track.hint') }}</p>
    <p class="ds-track-form__error" id="{{ $id }}-error" role="alert" @if (empty($error)) hidden @endif data-track-error>{{ empty($error) ? '' : __('store.track.error_'.$error) }}</p>
</form>
