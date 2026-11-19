{{-- "Designed & developed by RafaqTech" badge: logo + two lines, opens rafaqtech.com in a new tab.
     The styles sit in a nonce'd <style> because the store's security policy blocks style="" attributes. --}}
<style nonce="{{ Vite::cspNonce() }}">
    .rt-badge { display: inline-flex; align-items: center; gap: 0.625rem; padding: 0.5rem 1.125rem 0.5rem 0.625rem; background: #fff; border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 999px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06); text-decoration: none; color: #1a1a1a; line-height: 1.2; white-space: nowrap; transition: box-shadow 0.2s ease, transform 0.2s ease; }
    .rt-badge:hover { box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); transform: translateY(-1px); }
    .rt-badge:focus-visible { outline: 2px solid #7b5cff; outline-offset: 3px; }
    .rt-badge__logo { display: block; width: 30px; height: auto; flex: none; }
    .rt-badge__text { display: flex; flex-direction: column; text-align: start; }
    .rt-badge__by { font-size: 0.6875rem; color: #6b6b6b; }
    .rt-badge__name { font-size: 0.9375rem; font-weight: 700; color: #111; }
    .rt-badge-row { display: flex; justify-content: center; margin: 1.5rem 0 0.75rem; }
    .rt-badge-row--store { margin: 0 0 1.5rem; }
</style>
<a href="https://www.rafaqtech.com" target="_blank" rel="noopener" class="rt-badge">
    <img src="{{ asset('images/rafaqtech-mark.png') }}" alt="" width="30" height="28" class="rt-badge__logo">
    <span class="rt-badge__text">
        <span class="rt-badge__by">{{ __('Designed & developed by') }}</span>
        <span class="rt-badge__name">{{ __('RafaqTech') }}</span>
    </span>
</a>
