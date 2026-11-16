<x-filament-widgets::widget>
    <nav class="jude-statuses" aria-label="Orders by status">
        @foreach ($statuses as $row)
            <a class="jude-statuses__item @if ($row['status'] === \App\Domain\Orders\OrderStatus::New) jude-statuses__item--new @endif" href="{{ $row['url'] }}">
                <span class="jude-statuses__label">{{ $row['status']->label() }}</span>
                <strong class="jude-statuses__count">{{ $row['count'] }}</strong>
            </a>
        @endforeach
    </nav>
</x-filament-widgets::widget>
