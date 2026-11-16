@extends('layouts.store', ['page' => 'not-found', 'title' => __('store.not_found.tab_title'), 'private' => true])

@section('main')
    <div class="ds-section">
        @include('storefront.partials.empty', [
            'icon' => 'sparkles',
            'heading' => __('store.not_found.title'),
            'body' => __('store.not_found.body'),
            'action' => __('store.not_found.cta'),
            'url' => store_route('home'),
        ])
    </div>
@endsection
