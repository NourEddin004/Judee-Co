{{-- A JOD price; the script re-shows it in USD when the customer picks USD (REQ-DMO-39, 40). --}}
<span data-price="{{ $amount ?? '' }}">{{ \App\Support\Money::jod($amount ?? null) }}</span>
