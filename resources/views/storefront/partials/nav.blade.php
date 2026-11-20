@php
    $current = $page ?? '';
    $links = [
        ['route' => 'home', 'label' => __('store.header.home'), 'match' => ['home']],
        ['route' => 'shop', 'label' => __('store.header.shop'), 'match' => ['shop', 'product']],
    ];
@endphp
@foreach ($links as $link)
    <a href="{{ store_route($link['route']) }}" class="{{ $class }}" @if (in_array($current, $link['match'], true)) aria-current="page" @endif>{{ $link['label'] }}</a>
@endforeach
{{-- Track order opens a popup; without scripts, or on its own page, it is a plain link to /track. --}}
<a href="{{ store_route('track') }}" class="{{ $class }}" @if ($current === 'track') aria-current="page" @else aria-controls="track-order" aria-haspopup="dialog" data-modal-open="track-order" @endif>{{ __('store.track.nav') }}</a>
