<x-filament-widgets::widget>
    <div class="jude-summary">
        <a class="jude-summary__card" @if ($newOrdersUrl) href="{{ $newOrdersUrl }}" @endif>
            <span class="jude-summary__label">{{ __('New orders') }}</span>
            <strong class="jude-summary__value">{{ $newOrders }}</strong>
            <span class="jude-summary__note">{{ __('Waiting for you to confirm') }}</span>
        </a>
        <a class="jude-summary__card" @if ($productsUrl) href="{{ $productsUrl }}" @endif>
            <span class="jude-summary__label">{{ __('Products on the site') }}</span>
            <strong class="jude-summary__value">{{ $products }}</strong>
            <span class="jude-summary__note">{{ $productsNote }}</span>
        </a>
        @if ($addProduct || $editRack)
            <div class="jude-summary__actions">
                @if ($addProduct)
                    <x-filament::button tag="a" :href="$addProduct" icon="heroicon-o-plus" size="sm">{{ __('Add product') }}</x-filament::button>
                @endif
                @if ($editRack)
                    <x-filament::button tag="a" :href="$editRack" color="gray" outlined size="sm">{{ __('Edit hero rack') }}</x-filament::button>
                @endif
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
