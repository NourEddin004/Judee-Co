{{-- Live preview of the home page rack (REQ-RCK-11): the storefront's own markup and styles. --}}
<section class="jude-rack-preview" aria-label="Rack preview">
    <p class="jude-rack-preview__label">{{ __('Live preview') }}</p>
    @if ($rack->isEmpty())
        <p class="jude-rack-preview__empty">{{ __('No garments are shown. Add one, or switch one on below.') }}</p>
    @else
        @include('storefront.partials.rack', ['rack' => $rack, 'label' => __('Rack preview'), 'preview' => true])
    @endif
</section>
