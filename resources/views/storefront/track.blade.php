@extends('layouts.store', ['page' => 'track', 'title' => __('store.track.tab_title'), 'private' => true])

@section('main')
    <div class="ds-section">
        <div class="ds-track-page">
            <span class="ds-sheet__icon" aria-hidden="true">{!! icon('package') !!}</span>
            <h1 class="ds-track-page__title">{{ __('store.track.title') }}</h1>
            <p class="ds-track-page__intro">{{ __('store.track.intro') }}</p>
            @include('storefront.partials.track-form', ['id' => 'track-page-number'])
            <div class="ds-track-output" aria-live="polite" data-track-output>
                @if ($result)
                    @include('storefront.partials.track-result', ['result' => $result])
                @endif
            </div>
        </div>
    </div>
@endsection
