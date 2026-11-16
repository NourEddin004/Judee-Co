<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response the application sends (REQ-SEC-17, 18). nginx adds the same
 * headers to the files it serves itself (deploy/nginx/jude-security-headers.conf).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // A new nonce for each response: an inline script or style runs only if it carries it.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($nonce, $this->isAdminPage($request)));
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    /** A page of the admin panel itself, by the route that answered: a store page at a similar address is not. */
    private function isAdminPage(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'filament.');
    }

    private function policy(string $nonce, bool $admin): string
    {
        return implode('; ', [
            "default-src 'self'",
            $admin ? "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'" : "script-src 'self' 'nonce-{$nonce}'",
            $admin ? "style-src 'self' 'unsafe-inline'" : "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data: blob:",
            // The admin's photo upload resizes previews in a background worker it starts from a blob.
            $admin ? "worker-src 'self' blob:" : "worker-src 'self'",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
