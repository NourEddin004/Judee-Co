<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin's language, English or Arabic, chosen with the button in the top bar. It is kept in its
 * own cookie, so the admin and the shop can be in different languages. Arabic pages read right to left.
 */
class AdminLocale
{
    public const COOKIE = 'jude_admin_lang';

    /** One year, in minutes. */
    private const COOKIE_MINUTES = 525600;

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(self::current($request));

        return $next($request);
    }

    public static function current(Request $request): string
    {
        $saved = $request->cookie(self::COOKIE);

        return in_array($saved, SetLocale::LOCALES, true) ? $saved : 'en';
    }

    /** /admin/language/ar or /admin/language/en: saves the choice and goes back to the same page. */
    public static function switchTo(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        $back = url()->previous();
        $back = str_starts_with($back, url('/admin')) ? $back : url('/admin');

        return redirect()->to($back)->withCookie(cookie(self::COOKIE, $locale, self::COOKIE_MINUTES, '/admin', null, null, true, false, 'lax'));
    }
}
