{{-- "Back to dashboard" at the top of every admin page except the dashboard itself. --}}
@unless (request()->routeIs('filament.admin.pages.dashboard'))
    <a href="{{ \App\Filament\Pages\Dashboard::getUrl() }}" class="jude-admin-back">
        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedArrowLeft) }}
        <span>{{ __('Back to dashboard') }}</span>
    </a>
@endunless
