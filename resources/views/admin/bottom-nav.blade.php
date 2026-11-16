{{-- Phones: a bottom menu with the main sections and "More" for the full menu (SRS 4.1, admin preview). --}}
@if (\App\Filament\Support\BottomNav::shouldShow())
    <nav class="jude-bottom-nav" aria-label="Admin">
        @foreach (\App\Filament\Support\BottomNav::items() as $item)
            <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>
                {{ \Filament\Support\generate_icon_html($item['icon']) }}
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <button type="button" x-data x-on:click="$store.sidebar.open()">
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedEllipsisHorizontal) }}
            <span>{{ __('More') }}</span>
        </button>
    </nav>
@endif
