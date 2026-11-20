@php($freeFrom = config('store.free_delivery_from'))
@if ($freeFrom !== null)
    {{-- Fills as the bag grows toward free delivery in Jordan; the script sets the text and width (2026-11-20). --}}
    <div class="ds-freeship" hidden data-freeship>
        <p class="ds-freeship__text" data-freeship-text>{{ __('store.free_delivery.rule', ['amount' => \App\Support\Money::jod($freeFrom)]) }}</p>
        <div class="ds-freeship__track" role="progressbar" aria-label="{{ __('store.free_delivery.label') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-freeship-bar>
            <span class="ds-freeship__fill" data-freeship-fill></span>
        </div>
        <p class="ds-freeship__rule">{{ __('store.free_delivery.rule', ['amount' => \App\Support\Money::jod($freeFrom)]) }}</p>
    </div>
@endif
