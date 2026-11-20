{{-- Where an order is: status, the four steps, and the safe facts (no name, phone or street). --}}
<div class="ds-track-result {{ $result['cancelled'] ? 'is-cancelled' : '' }}" data-status="{{ $result['status'] }}">
    <div class="ds-track-result__head">
        <p class="ds-track-result__number">{{ __('store.track.order', ['number' => $result['number']]) }}</p>
        <p class="ds-track-result__status">
            <span class="ds-track-badge ds-track-badge--{{ $result['status'] }}">{{ $result['status_label'] }}</span>
        </p>
        <p class="ds-track-result__message">{{ $result['message'] }}</p>
    </div>
    @unless ($result['cancelled'])
        <ol class="ds-track-steps" aria-label="{{ __('store.track.title') }}">
            @foreach ($result['steps'] as $step)
                <li class="ds-track-steps__step {{ $step['done'] ? 'is-done' : '' }} {{ $step['current'] ? 'is-current' : '' }}" @if ($step['current']) aria-current="step" @endif>
                    <span class="ds-track-steps__dot" aria-hidden="true">@if ($step['done']){!! icon('check', 'ds-icon--sm') !!}@endif</span>
                    <span class="ds-track-steps__label">{{ $step['label'] }}</span>
                </li>
            @endforeach
        </ol>
    @endunless
    <dl class="ds-track-facts">
        <div class="ds-track-facts__item"><dt>{{ __('store.track.placed') }}</dt><dd>{{ $result['placed'] }}</dd></div>
        <div class="ds-track-facts__item"><dt>{{ __('store.track.items') }}</dt><dd>{{ $result['pieces'] }}</dd></div>
        <div class="ds-track-facts__item"><dt>{{ __('store.track.total') }}</dt><dd><span data-price="{{ $result['total_jod'] }}">{{ $result['total'] }}</span></dd></div>
        @if ($result['city'])
            <div class="ds-track-facts__item"><dt>{{ __('store.track.city') }}</dt><dd>{{ $result['city'] }}</dd></div>
        @endif
    </dl>
    <p class="ds-track-result__help">{{ __('store.track.help') }} <a href="{{ store_route('contact') }}">{{ __('store.track.contact') }}</a></p>
</div>
