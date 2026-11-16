<?php

namespace Tests;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Every feature test starts from the demo seed data (Appendix D.8). */
    protected bool $seed = true;

    private bool $nextInArabic = false;

    /** The next request comes from a customer who chose Arabic with the language button. */
    protected function inArabic(): static
    {
        $this->nextInArabic = true;

        return $this;
    }

    /**
     * Each test request starts fresh, as a real request does (request-scoped services such as SiteContent).
     * An address written "ar:/shop" is /shop for a customer who chose Arabic (data providers use it).
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->forgetScopedInstances();

        if (str_starts_with($uri, 'ar:')) {
            $uri = substr($uri, 3);
            $this->nextInArabic = true;
        }

        if ($this->nextInArabic) {
            $cookies[SetLocale::COOKIE] = 'ar';
            $this->nextInArabic = false;
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
