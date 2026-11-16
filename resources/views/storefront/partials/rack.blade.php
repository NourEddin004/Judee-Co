{{-- The hanging rack: the home page hero (D.6.1) and the admin's live preview (REQ-RCK-11), one markup for both.
     sizes: the widest garment's width on screen, so a phone downloads the small copies (REQ-PRF-02). --}}
@php($preview ??= false)
@php($adjusted = $rack->filter(fn ($garment) => $garment->size_adjust !== null && (float) $garment->size_adjust !== 1.0))
@if (! $preview && $adjusted->isNotEmpty())
    @push('styles')
        {{-- The owner's size adjustments, in a style block the page's security policy allows (REQ-SEC-17). --}}
        <style nonce="{{ Vite::cspNonce() }}">@foreach ($adjusted as $garment)[data-rack-garment="{{ $garment->id }}"]{--rack-adjust:{{ (float) $garment->size_adjust }}}@endforeach</style>
    @endpush
@endif
<div class="ds-hero__stage" role="group" aria-label="{{ $label }}">
    <span class="ds-hero__ground" aria-hidden="true"></span>
    <span class="ds-rack__frame" aria-hidden="true">
        <span class="ds-rack__rail"></span>
        <span class="ds-rack__post ds-rack__post--left"></span>
        <span class="ds-rack__post ds-rack__post--right"></span>
        <span class="ds-rack__foot ds-rack__foot--left"></span>
        <span class="ds-rack__foot ds-rack__foot--right"></span>
    </span>
    <ul class="ds-rack__items">
        @foreach ($rack as $garment)
            <li class="ds-rack__item">
                <{{ $preview ? 'span' : 'a' }} class="ds-rack__garment"
                    @unless ($preview) href="{{ $garment->url() }}" @endunless>
                    {!! icon('hanger', 'ds-rack__hanger') !!}
                    <img class="ds-rack__image" src="{{ Storage::disk('public')->url($garment->image_path) }}" srcset="{{ srcset($garment->image_path) }}" sizes="(min-width: 100rem) 185px, (min-width: 48.0625rem) 12vw, 21vw" alt="{{ $garment->t('alt') }}"
                         data-rack-garment="{{ $garment->id }}" @if ($preview && $adjusted->contains($garment)) style="--rack-adjust: {{ (float) $garment->size_adjust }}" @endif>
                    <span class="ds-rack__tag">{{ $garment->t('name') }}</span>
                </{{ $preview ? 'span' : 'a' }}>
            </li>
        @endforeach
    </ul>
</div>
