<?php

use App\Http\Middleware\EnsureCitizenIsActive;
use App\Http\Middleware\EnsureMesaPartesIsEnabled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        // Outside local development, only requests for the domain of APP_URL (and its subdomains) are answered.
        // Proxies in front of the site are listed in TRUSTED_PROXIES (config/trustedproxy.php).
        $middleware->trustHosts();

        // Citizens of the Mesa de Partes sign in on their own page; everyone else on the panel's.
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('mesa-de-partes', 'mesa-de-partes/*')
            ? route('mesa-partes.login')
            : route('filament.admin.auth.login'));
        $middleware->redirectUsersTo(fn (): string => route('mesa-partes.account'));

        $middleware->alias([
            'mesa-partes.enabled' => EnsureMesaPartesIsEnabled::class,
            'mesa-partes.active' => EnsureCitizenIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
