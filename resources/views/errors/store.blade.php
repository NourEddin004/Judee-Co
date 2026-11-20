{{-- A 4xx error page inside the store's own header and footer. --}}
@extends('layouts.store', ['page' => 'error', 'title' => $errorPage['tab'], 'private' => true])

@section('main')
    <div class="ds-section">
        <div class="ds-empty ds-error">
            <span class="ds-empty__icon">{!! icon($errorPage['icon']) !!}</span>
            <p class="ds-error__code">{{ $errorPage['code'] }}</p>
            <h1 class="ds-empty__title">{{ $errorPage['title'] }}</h1>
            <p class="ds-empty__body">{{ $errorPage['body'] }}</p>
            <div class="ds-error__actions">
                @foreach ($errorPage['actions'] as $action)
                    <a href="{{ $action['url'] }}" class="ds-btn {{ $action['primary'] ? 'ds-btn--primary' : 'ds-btn--secondary' }}">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
