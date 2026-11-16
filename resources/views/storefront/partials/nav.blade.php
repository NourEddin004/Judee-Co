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
