<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Redirect authenticated users away from guest-only pages (login, register)
        $middleware->redirectUsersTo(function ($request) {
            return $request->user()?->isAdmin() ? route('dashboard') : route('home');
        });

        // Exclude test routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'poll-vote-test/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
