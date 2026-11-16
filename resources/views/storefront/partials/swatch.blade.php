{{-- Colour swatch. The stylesheet gives each shade its colour (components/swatch.css), so the page needs no inline style (REQ-SEC-17). --}}
<span class="ds-swatch {{ $class ?? '' }}" data-shade="{{ $shade->key }}" aria-hidden="true"></span>
