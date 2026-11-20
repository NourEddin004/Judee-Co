<?php

use App\Http\Middleware\SameOriginOnly;
use App\Http\Middleware\ScrubInvalidText;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\ErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['locale' => SetLocale::class, 'same-origin' => SameOriginOnly::class]);
        // The language cookie holds only "en" or "ar"; it stays readable on the Not Found page,
        // which Laravel answers before the cookies of the web group are decrypted.
        $middleware->encryptCookies(except: [SetLocale::COOKIE]);
        // Outermost, so every response gets the headers: the admin panel's (its routes do not use the
        // web group) and the ones Laravel's own middleware answers with, such as a bad address (400).
        $middleware->prepend(SecurityHeaders::class);
        // After Laravel's own clean-up of typed-in values (trimming, empty to null).
        $middleware->append(ScrubInvalidText::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every error page is in the visitor's language (D.6.10): the shop's, or the admin's in the admin.
        $exceptions->render(function (Throwable $exception, Request $request) {
            // The language button on the Not Found page works as on every other page.
            if ($exception instanceof NotFoundHttpException && ! ErrorPage::isAdmin($request)
                && $request->isMethodSafe() && $request->query->has('lang')) {
                return SetLocale::switchTo($request);
            }

            app()->setLocale(ErrorPage::locale($request));

            return null;
        });
    })->create();
