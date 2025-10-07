<?php
// middleware class
use App\Http\Middleware\SetLocaleFromSession;

// laravel
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Set language from session, append after other web middleware
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocaleFromSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
