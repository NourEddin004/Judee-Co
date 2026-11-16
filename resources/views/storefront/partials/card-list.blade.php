{{-- $eager: how many cards from the top load their photo at once, because they are on screen when the page opens (REQ-PRF-02). --}}
@foreach ($products as $product)
    @include('storefront.partials.card', ['eager' => $loop->index < ($eager ?? 0), 'first' => $loop->index < ($eager ?? 0)])
@endforeach
