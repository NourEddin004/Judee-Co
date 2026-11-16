{{-- Empty state (D.5.7). --}}
<div class="ds-empty">
    <span class="ds-empty__icon">{!! icon($icon) !!}</span>
    <h2 class="ds-empty__title">{{ $heading }}</h2>
    <p class="ds-empty__body">{{ $body }}</p>
    <a href="{{ $url }}" class="ds-btn ds-btn--primary">{{ $action }}</a>
</div>
