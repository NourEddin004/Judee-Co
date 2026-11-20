{{-- The stand-alone error page: server errors, maintenance, and every admin error. It reads nothing
     from the database and loads no built assets, so it still shows when something larger is broken.
     Colors and type are the JUDE tokens (resources/css/tokens.css), copied here for that reason. --}}
@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $nonce = \Illuminate\Support\Facades\Vite::cspNonce();
    $admin = $errorPage['admin'];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $errorPage['tab'] }} | JUDE</title>
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <style @if ($nonce) nonce="{{ $nonce }}" @endif>
        @font-face { font-family: "Montserrat"; font-weight: 400; font-display: swap; src: url("{{ asset('fonts/montserrat-latin-400-normal.woff2') }}") format("woff2"); }
        @font-face { font-family: "Montserrat"; font-weight: 600; font-display: swap; src: url("{{ asset('fonts/montserrat-latin-600-normal.woff2') }}") format("woff2"); }
        @font-face { font-family: "Merriweather"; font-weight: 400; font-display: swap; src: url("{{ asset('fonts/merriweather-latin-400-normal.woff2') }}") format("woff2"); }
        @font-face { font-family: "IBM Plex Sans Arabic"; font-weight: 400; font-display: swap; src: url("{{ asset('fonts/ibm-plex-sans-arabic-arabic-400-normal.woff2') }}") format("woff2"); }
        @font-face { font-family: "IBM Plex Sans Arabic"; font-weight: 600; font-display: swap; src: url("{{ asset('fonts/ibm-plex-sans-arabic-arabic-600-normal.woff2') }}") format("woff2"); }
        :root {
            --ink: #2e1c22; --ink-muted: #735e64; --rose: #a24f68; --rose-strong: #874058; --plum: #4a2633;
            --gold: #eab2bf; --on-brand: #fff7f9; --blush-50: #fdf6f8; --blush-200: #f6dde4; --wine-deep: #6e3547;
            --line: #8c445924; --line-dashed: #e5cbd2;
            --body: "Montserrat", ui-sans-serif, system-ui, sans-serif; --display: "Merriweather", Georgia, serif;
        }
        :root[lang="ar"] { --body: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, sans-serif; --display: var(--body); }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { margin: 0; min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; font-family: var(--body); color: var(--ink); background: var(--blush-50); }
        .jude-err-top { display: flex; align-items: center; justify-content: center; gap: .75rem; padding: 1.25rem 1rem; border-bottom: 1px solid var(--line); background: #fff; }
        .jude-err-top img { display: block; height: 2rem; width: auto; }
        .jude-err--admin .jude-err-top { background: var(--plum); border-bottom-color: transparent; justify-content: flex-start; padding-inline: 1.5rem; }
        .jude-err--admin .jude-err-top img { height: 1.75rem; }
        .jude-err-top__label { color: var(--gold); font-size: .8125rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        :root[lang="ar"] .jude-err-top__label { letter-spacing: 0; }
        .jude-err-main { flex: 1; display: grid; place-items: center; padding: 2rem 1rem; }
        .jude-err-card { width: 100%; max-width: 34rem; padding: 2.5rem 1.5rem; text-align: center; background: #fff; border: 1px dashed var(--line-dashed); border-radius: 1.5rem; }
        .jude-err--admin .jude-err-card { border-style: solid; border-color: var(--line); border-radius: 1rem; box-shadow: 0 1px 3px #4a26330f; }
        .jude-err-icon { width: 3.5rem; height: 3.5rem; margin: 0 auto; display: grid; place-items: center; border-radius: 999px; background: var(--blush-200); color: var(--wine-deep); }
        .jude-err-icon svg { width: 1.5rem; height: 1.5rem; }
        .jude-err-code { margin: 1rem 0 0; font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--rose-strong); }
        :root[lang="ar"] .jude-err-code { letter-spacing: 0; }
        h1 { margin: .5rem 0 0; font-family: var(--display); font-weight: 400; font-size: 1.625rem; line-height: 1.3; }
        .jude-err-body { margin: .75rem auto 1.75rem; max-width: 28rem; font-size: 1rem; line-height: 1.65; color: var(--ink-muted); }
        .jude-err-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; }
        .jude-err-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 3rem; padding: .75rem 1.5rem; border: 1px solid var(--rose); border-radius: .75rem; font: inherit; font-size: .875rem; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; text-decoration: none; color: var(--rose-strong); background: #fff; transition: background-color .2s ease; }
        :root[lang="ar"] .jude-err-btn { letter-spacing: 0; }
        .jude-err-btn:hover { background: var(--blush-50); }
        .jude-err-btn--primary { background: var(--rose); border-color: var(--rose); color: var(--on-brand); }
        .jude-err-btn--primary:hover { background: var(--rose-strong); }
        .jude-err-btn:focus-visible { outline: 2px solid var(--rose-strong); outline-offset: 3px; }
        .jude-err-foot { padding: 0 1rem 1rem; }
        @media (min-width: 48.0625rem) { .jude-err-card { padding: 3rem 2.5rem; } h1 { font-size: 1.875rem; } }
        @media (max-width: 30rem) { .jude-err-actions { flex-direction: column; } }
        @media (prefers-reduced-motion: reduce) { .jude-err-btn { transition: none; } }
    </style>
</head>
<body class="{{ $admin ? 'jude-err--admin' : 'jude-err--store' }}">
    <header class="jude-err-top">
        <a href="{{ $admin ? url('/admin') : url('/') }}"><img src="{{ asset('images/jude-logo.png') }}" alt="JUDE"></a>
        @if ($admin)
            <span class="jude-err-top__label">{{ __('store.errors.admin') }}</span>
        @endif
    </header>
    <main class="jude-err-main">
        <div class="jude-err-card">
            <span class="jude-err-icon" aria-hidden="true">{!! icon($errorPage['icon']) !!}</span>
            <p class="jude-err-code">{{ $errorPage['code'] }}</p>
            <h1>{{ $errorPage['title'] }}</h1>
            <p class="jude-err-body">{{ $errorPage['body'] }}</p>
            <div class="jude-err-actions">
                @foreach ($errorPage['actions'] as $action)
                    <a href="{{ $action['url'] }}" class="jude-err-btn {{ $action['primary'] ? 'jude-err-btn--primary' : '' }}">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </div>
    </main>
    <footer class="jude-err-foot">
        <div class="rt-badge-row">
            @include('partials.rafaqtech-badge')
        </div>
    </footer>
</body>
</html>
