<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The storefront endpoints answer only the store's own pages (REQ-SEC-40). A browser names the
 * page that sent a request in the Origin header; a request from any other site is refused.
 */
class SameOriginOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        if ($origin !== null && ! in_array(rtrim($origin, '/'), $this->allowed($request), true)) {
            abort(403);
        }

        if ($request->headers->get('Sec-Fetch-Site') === 'cross-site') {
            abort(403);
        }

        return $next($request);
    }

    /** @return list<string> */
    private function allowed(Request $request): array
    {
        $app = parse_url((string) config('app.url'));
        $configured = isset($app['scheme'], $app['host'])
            ? $app['scheme'].'://'.$app['host'].(isset($app['port']) ? ':'.$app['port'] : '')
            : null;

        return array_values(array_filter([$configured, $request->getSchemeAndHttpHost()]));
    }
}
