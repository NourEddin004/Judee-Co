<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Both languages share one address per page (REQ-STR-18, REQ-STR-19). The language is kept in a
 * cookie: English until the customer presses the language button. The button opens the same
 * address with ?lang=ar (or ?lang=en); this saves the choice and sends the browser straight back
 * to the address without ?lang, so the address bar never changes (REQ-STR-20, REQ-DMO-84).
 */
class SetLocale
{
    public const LOCALES = ['en', 'ar'];

    public const COOKIE = 'jude_lang';

    /** One year, in minutes. */
    private const COOKIE_MINUTES = 525600;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() && $request->query->has('lang')) {
            return self::switchTo($request);
        }

        app()->setLocale(self::fromRequest($request));

        $response = $next($request);
        // The same address answers in two languages, so no cache may keep one for everybody.
        $response->setVary(array_unique([...$response->getVary(), 'Cookie']));

        return $response;
    }

    /** Saves the ?lang= choice and sends the browser back to the same address without it. */
    public static function switchTo(Request $request): RedirectResponse
    {
        $asked = $request->query('lang');
        $response = redirect()->to(self::withoutLang($request), 302);

        return is_string($asked) && in_array($asked, self::LOCALES, true)
            ? $response->withCookie(self::cookie($asked))
            : $response;
    }

    /** The language saved in the customer's cookie; English when there is none. */
    public static function fromRequest(Request $request): string
    {
        $saved = $request->cookies->get(self::COOKIE);

        return is_string($saved) && in_array($saved, self::LOCALES, true) ? $saved : 'en';
    }

    public static function cookie(string $locale): Cookie
    {
        return cookie(self::COOKIE, $locale, self::COOKIE_MINUTES, '/', null, config('session.secure'), true, false, 'lax');
    }

    /** The address as typed, with every lang=... part of the query string taken out. */
    public static function withoutLang(Request $request): string
    {
        $parts = array_filter(
            explode('&', (string) $request->server('QUERY_STRING')),
            fn (string $part) => $part !== '' && ! preg_match('/^lang(=|$)/', $part),
        );

        return $request->url().($parts ? '?'.implode('&', $parts) : '');
    }
}
