<?php

namespace App\Support;

use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * What a branded error page says and where its buttons lead, in the visitor's language.
 * It reads nothing from the database, so the server-error and maintenance pages still show
 * when the database is down.
 */
class ErrorPage
{
    /** Codes with their own wording; any other code gets the general "something did not go through". */
    private const WORDED = [403, 404, 419, 429, 500, 503];

    private const ICONS = [403 => 'user', 404 => 'sparkles', 419 => 'shopping-bag', 429 => 'sparkles', 500 => 'sparkles', 503 => 'sparkles'];

    public static function isAdmin(Request $request): bool
    {
        return $request->is('admin', 'admin/*');
    }

    /** The language for an error page: the admin's own cookie in the admin, the shop's everywhere else. */
    public static function locale(Request $request): string
    {
        if (! self::isAdmin($request)) {
            return SetLocale::fromRequest($request);
        }

        $raw = $request->cookies->get(AdminLocale::COOKIE);
        if (in_array($raw, SetLocale::LOCALES, true)) {
            return $raw;
        }

        // A Not Found page in the admin is answered before the cookies are decrypted.
        try {
            $value = CookieValuePrefix::validate(AdminLocale::COOKIE, Crypt::decrypt((string) $raw, false), Crypt::getAllKeys());
        } catch (Throwable) {
            $value = null;
        }

        return in_array($value, SetLocale::LOCALES, true) ? $value : 'en';
    }

    /**
     * @return array{status: int, admin: bool, code: string, icon: string, tab: string, title: string, body: string, actions: list<array{label: string, url: string, primary: bool}>}
     */
    public static function for(int $status, Request $request): array
    {
        $admin = self::isAdmin($request);
        $key = in_array($status, self::WORDED, true) ? (string) $status : 'other';

        if ($status === 404) {
            $text = ['tab' => __('store.not_found.tab_title'), 'title' => __('store.not_found.title'), 'body' => __('store.not_found.body')];
        } else {
            $text = __('store.errors.'.$key);
        }

        if ($admin && $key !== 'other') {
            $text['body'] = __('store.errors.admin_body.'.$key);
        }

        return [
            'status' => $status,
            'admin' => $admin,
            'code' => __('store.errors.code', ['code' => $status]),
            'icon' => self::ICONS[$status] ?? 'sparkles',
            'tab' => $text['tab'],
            'title' => $text['title'],
            'body' => $text['body'],
            'actions' => $admin ? self::adminActions($status, $request) : self::storeActions($status, $request),
        ];
    }

    /** @return list<array{label: string, url: string, primary: bool}> */
    private static function storeActions(int $status, Request $request): array
    {
        $home = ['label' => __('store.errors.home'), 'url' => self::url('home', '/'), 'primary' => true];
        $shop = ['label' => __('store.errors.shop'), 'url' => self::url('shop', '/shop'), 'primary' => false];

        return match (true) {
            $status === 419 => [['label' => __('store.errors.back'), 'url' => self::previous($request), 'primary' => true], $home],
            in_array($status, [429, 500, 503], true) => [['label' => __('store.errors.retry'), 'url' => self::retry($request), 'primary' => true], $status === 503 ? $home : $shop],
            $status === 403 => [$home, ['label' => __('store.errors.contact'), 'url' => self::url('contact', '/contact'), 'primary' => false]],
            default => [$home, $shop],
        };
    }

    /** @return list<array{label: string, url: string, primary: bool}> */
    private static function adminActions(int $status, Request $request): array
    {
        $dashboard = ['label' => __('store.errors.dashboard'), 'url' => url('/admin'), 'primary' => true];

        return match (true) {
            $status === 419 => [['label' => __('store.errors.sign_in'), 'url' => url('/admin/login'), 'primary' => true]],
            in_array($status, [429, 500, 503], true) => [['label' => __('store.errors.retry'), 'url' => self::retry($request), 'primary' => true], [...$dashboard, 'primary' => false]],
            default => [$dashboard],
        };
    }

    /** "Try again" opens the same address again; after a form was sent it goes back to the page with the form. */
    private static function retry(Request $request): string
    {
        return $request->isMethodSafe() ? $request->fullUrl() : self::previous($request);
    }

    /** The page the visitor came from, if it is on this site; the home page otherwise. */
    private static function previous(Request $request): string
    {
        $home = url('/');
        $previous = (string) $request->headers->get('referer');

        return $previous !== '' && str_starts_with($previous, $home) ? $previous : $home;
    }

    /** A store address by its route name, without touching the database. */
    private static function url(string $route, string $fallback): string
    {
        try {
            return function_exists('store_route') ? store_route($route) : url($fallback);
        } catch (Throwable) {
            return url($fallback);
        }
    }
}
