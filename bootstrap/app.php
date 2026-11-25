<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API guests get no redirect (the exception handler answers 401 JSON); web guests go to the Breeze login page
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients always get JSON errors; otherwise a missing token would try to redirect to a login page
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
