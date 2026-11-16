<?php

namespace Tests\Feature\Security;

use App\Models\RackItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * The headers on every response (REQ-SEC-17, 18), the production cookie and debug settings
 * (REQ-SEC-21, 26) and error pages without technical details (REQ-SEC-27).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public static function storefrontPages(): array
    {
        return [
            'home' => ['/'], 'arabic home' => ['ar:/'], 'shop' => ['/shop'], 'search' => ['/shop?q=olive'],
            'category' => ['/hijabs'], 'product' => ['/product/black-wrap-set'], 'bag' => ['/cart'],
            'arabic checkout' => ['ar:/checkout'], 'contact' => ['/contact'],
            'terms' => ['/terms'], 'not found' => ['/no-such-page'],
        ];
    }

    /** @return array<string, string> directive => its sources */
    private function policy(TestResponse $response): array
    {
        $directives = [];
        foreach (explode(';', (string) $response->headers->get('Content-Security-Policy')) as $directive) {
            [$name, $sources] = array_pad(explode(' ', trim($directive), 2), 2, '');
            $directives[$name] = $sources;
        }

        return $directives;
    }

    private function nonce(TestResponse $response): string
    {
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $this->policy($response)['script-src'], $match);

        return $match[1];
    }

    #[Test]
    #[DataProvider('storefrontPages')]
    public function req_sec_17_storefront_pages_allow_only_the_stores_own_scripts_and_styles(string $path): void
    {
        $response = $this->get($path);
        $policy = $this->policy($response);
        $html = $response->getContent();

        $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9]{40}'$/", $policy['script-src']);
        $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9]{40}'$/", $policy['style-src']);
        $this->assertSame("'self'", $policy['default-src']);
        $this->assertSame("'none'", $policy['object-src']);
        $this->assertSame("'self'", $policy['base-uri']);
        $this->assertSame("'self'", $policy['frame-ancestors']);

        // Every script and style the page carries is the store's own: built files, or inline with this response's nonce.
        $nonce = $this->nonce($response);
        preg_match_all('/<(script|style)\b[^>]*>/', $html, $tags);
        foreach ($tags[0] as $tag) {
            if (str_contains($tag, 'type="application/json"')) {
                continue;   // data, never run
            }
            $this->assertTrue(str_contains($tag, "nonce=\"{$nonce}\""), "Tag without this page's nonce: {$tag}");
        }
        $this->assertDoesNotMatchRegularExpression('/\sstyle="/', $html, 'An inline style attribute would be blocked');
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="/', $html, 'An inline event handler would be blocked');
    }

    #[Test]
    public function req_sec_17_the_home_rack_sizes_use_a_style_block_the_policy_allows(): void
    {
        RackItem::query()->first()->update(['size_adjust' => 0.85]);

        $response = $this->get('/');
        $nonce = $this->nonce($response);

        $response->assertSee("<style nonce=\"{$nonce}\">", false);
        $response->assertSee('{--rack-adjust:0.85}', false);
    }

    #[Test]
    public function req_sec_17_a_new_nonce_for_every_response(): void
    {
        $this->assertNotSame($this->policy($this->get('/'))['script-src'], $this->policy($this->get('/'))['script-src']);
    }

    #[Test]
    public function req_sec_17_the_admin_panel_allows_scripts_only_from_the_store_itself(): void
    {
        $policy = $this->policy($this->get('/admin/login'));

        // The admin panel (Filament, SRS 2.5) needs eval for its Alpine expressions and inline styles;
        // both accepted for the admin only (decided 2026-10-24). No other site may supply a script.
        $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9]{40}' 'unsafe-eval'$/", $policy['script-src']);
        $this->assertSame("'self' 'unsafe-inline'", $policy['style-src']);
        $this->assertSame("'self'", $policy['default-src']);
    }

    #[Test]
    public function req_sec_17_a_store_page_at_an_admin_like_address_keeps_the_store_policy(): void
    {
        // The admin allowances follow the admin panel's own pages, not their addresses: these are the
        // store's Not Found page.
        foreach (['/admin/no-such-page', '/livewire-sale', '/livewire'] as $path) {
            $policy = $this->policy($this->get($path)->assertNotFound());

            $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9]{40}'$/", $policy['script-src'], $path);
            $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9]{40}'$/", $policy['style-src'], $path);
        }
    }

    public static function everyKindOfResponse(): array
    {
        return [
            'page' => ['GET', '/shop'], 'arabic page' => ['GET', 'ar:/product/black-wrap-set'],
            'not found' => ['GET', '/no-such-page'], 'admin login' => ['GET', '/admin/login'],
            'redirect' => ['GET', '/admin'], 'endpoint' => ['POST', '/bag/check'],
            'health check' => ['GET', '/up'], 'sitemap' => ['GET', '/sitemap.xml'],
            // Answered by Laravel's own middleware, before any route.
            'bad address' => ['GET', '/shop%FF'],
        ];
    }

    #[Test]
    #[DataProvider('everyKindOfResponse')]
    public function req_sec_18_every_response_sends_the_security_headers(string $method, string $path): void
    {
        $response = $this->call($method, $path, server: ['HTTP_ACCEPT' => 'application/json']);

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function req_sec_18_the_maintenance_page_sends_the_security_headers_too(): void
    {
        $this->artisan('down')->assertSuccessful();

        try {
            $response = $this->get('/shop')->assertServiceUnavailable();
        } finally {
            $this->artisan('up');
        }

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function req_sec_18_nginx_adds_the_headers_to_the_files_it_serves_itself(): void
    {
        $snippet = file_get_contents(base_path('deploy/nginx/jude-security-headers.conf'));
        foreach (['Strict-Transport-Security "max-age=31536000; includeSubDomains"', 'X-Frame-Options "SAMEORIGIN"', 'X-Content-Type-Options "nosniff"', 'Referrer-Policy "strict-origin-when-cross-origin"'] as $header) {
            $this->assertStringContainsString("add_header {$header} always;", $snippet);
        }

        // The main server block includes the snippet, so everything nginx answers gets it, its own error
        // pages too. A location with an add_header of its own must include it again.
        $nginx = file_get_contents(base_path('deploy/nginx/jude.conf'));
        $main = substr($nginx, strrpos($nginx, "\nserver {"));
        $this->assertMatchesRegularExpression('/^    include snippets\/jude-security-headers\.conf;$/m', $main);
        $block = fn (string $location) => strstr(substr($main, strpos($main, "\n    {$location}\n")), "\n    }\n", true);
        $this->assertStringContainsString('add_header Cache-Control', $block('location ~* ^/(build|fonts)/ {'));
        $this->assertStringContainsString('include snippets/jude-security-headers.conf;', $block('location ~* ^/(build|fonts)/ {'));

        // Pages: nginx's copies replace the site's, so each header is sent once.
        $php = $block('location ~ \.php$ {');
        $this->assertStringContainsString('fastcgi_pass', $php);
        $this->assertStringNotContainsString('add_header', $php);
        foreach (['Strict-Transport-Security', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy'] as $header) {
            $this->assertStringContainsString("fastcgi_hide_header {$header};", $php);
        }
        $this->assertSame(1, preg_match_all('/^\s*add_header /m', $main), 'No other location has an add_header of its own');
    }

    /** @return array<string, string> the production settings template */
    private function productionSettings(): array
    {
        $settings = [];
        foreach (file(base_path('deploy/env.production.example'), FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match('/^([A-Z_]+)=(.*)$/', $line, $match)) {
                $settings[$match[1]] = trim($match[2], '"');
            }
        }

        return $settings;
    }

    #[Test]
    public function req_sec_21_with_the_production_settings_the_session_cookie_is_secure_httponly_and_samesite(): void
    {
        $settings = $this->productionSettings();
        $this->assertSame(['true', 'true', 'lax'], [$settings['SESSION_SECURE_COOKIE'], $settings['SESSION_HTTP_ONLY'], $settings['SESSION_SAME_SITE']]);

        config(['session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax']);
        $cookie = collect($this->get('https://localhost/shop')->headers->getCookies())
            ->firstWhere(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    #[Test]
    public function req_sec_26_debug_is_off_in_the_production_settings(): void
    {
        $settings = $this->productionSettings();

        $this->assertSame('production', $settings['APP_ENV']);
        $this->assertSame('false', $settings['APP_DEBUG']);
        // Left out of .env, debug is off too.
        $this->assertStringContainsString("'debug' => (bool) env('APP_DEBUG', false)", file_get_contents(config_path('app.php')));
    }

    public static function failures(): array
    {
        return [
            'code error' => [fn () => throw new RuntimeException('Undefined index in /var/www/jude/current/app/Domain/Secret.php on line 12')],
            'database error' => [fn () => throw new QueryException('mysql', 'select * from `orders` where `phone` = ?', ['0791234567'], new RuntimeException('SQLSTATE[42S22]: Column not found'))],
        ];
    }

    #[Test]
    #[DataProvider('failures')]
    public function req_sec_27_error_pages_show_no_technical_details(\Closure $failure): void
    {
        config(['app.debug' => false]);
        Route::get('/_failing-page', $failure);

        foreach ([$this->get('/_failing-page'), $this->getJson('/_failing-page')] as $response) {
            $response->assertStatus(500);
            $body = $response->getContent();
            foreach (['/var/www', 'Secret.php', 'line 12', 'SQLSTATE', 'select *', 'orders', '0791234567', 'RuntimeException', 'Stack trace', 'vendor/'] as $detail) {
                $this->assertStringNotContainsString($detail, $body);
            }
        }
        $this->getJson('/_failing-page')->assertExactJson(['message' => 'Server Error']);
    }
}
