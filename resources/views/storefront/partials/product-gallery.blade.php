{{-- The photos of one colour: main frame and thumbnails (D.5.8). Thumbnails show only for 2 or more photos. --}}
{{-- At most 4: the main photo first, then the others in the owner's order (REQ-STR-30). --}}
@php($photos = $color->photos->sortByDesc('is_main')->values()->take(config('store.max_photos_per_color')))
<div class="ds-pdp__frame">
    <img class="ds-pdp__image" src="{{ $photos->first()?->url() }}" srcset="{{ srcset($photos->first()?->path) }}" sizes="(min-width: 48.0625rem) 600px, calc(100vw - 2rem)" alt="{{ __('store.product.image_alt', ['product' => $color->name()]) }}" @if ($live ?? false) fetchpriority="high" @endif data-gallery-main>
</div>
@if ($photos->count() > 1)
    <div class="ds-pdp__thumbs" role="group" aria-label="{{ __('store.product.gallery') }}">
        @foreach ($photos as $photo)
            <button type="button" class="ds-pdp__thumb {{ $loop->first ? 'is-active' : '' }}" aria-label="{{ __('store.product.view_image', ['number' => $loop->iteration]) }}" @if ($loop->first) aria-current="true" @endif data-gallery-thumb="{{ $photo->url() }}" data-gallery-srcset="{{ srcset($photo->path) }}">
                <img class="ds-pdp__thumb-image" src="{{ $photo->smallUrl() }}" srcset="{{ srcset($photo->path) }}" sizes="4.75rem" alt="" loading="lazy">
            </button>
        @endforeach
    </div>
@endif
