{{-- Pagination (D.5.7, REQ-DMO-23); hidden when there is only one page. --}}
@if ($result->pageCount > 1)
    @php($pageUrl = fn (int $number) => category_url($categorySlug, $result->query(['page' => $number])))
    <nav class="ds-pagination" aria-label="{{ __('store.shop.product_pages') }}">
        @if ($result->page > 1)
            <a href="{{ $pageUrl($result->page - 1) }}" class="ds-pagination__item" aria-label="{{ __('store.shop.previous_page') }}">{!! icon('chevron-left') !!}</a>
        @else
            <span class="ds-pagination__item is-disabled" aria-hidden="true">{!! icon('chevron-left') !!}</span>
        @endif
        @foreach ($result->pageNumbers() as $number)
            @if ($number === null)
                <span class="ds-pagination__gap" aria-hidden="true">…</span>
            @else
                <a href="{{ $pageUrl($number) }}" class="ds-pagination__item {{ $number === $result->page ? 'is-current' : '' }}" aria-label="{{ __('store.shop.page', ['number' => $number]) }}" @if ($number === $result->page) aria-current="page" @endif>{{ $number }}</a>
            @endif
        @endforeach
        @if ($result->page < $result->pageCount)
            <a href="{{ $pageUrl($result->page + 1) }}" class="ds-pagination__item" aria-label="{{ __('store.shop.next_page') }}">{!! icon('chevron-right') !!}</a>
        @else
            <span class="ds-pagination__item is-disabled" aria-hidden="true">{!! icon('chevron-right') !!}</span>
        @endif
    </nav>
@endif
