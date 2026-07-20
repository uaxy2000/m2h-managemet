<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware([])->group(function () {
                \Illuminate\Support\Facades\Route::get('/webhook/meta', [\App\Http\Controllers\MetaWebhookController::class, 'verify']);
                \Illuminate\Support\Facades\Route::post('/webhook/meta', [\App\Http\Controllers\MetaWebhookController::class, 'receive']);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->validateCsrfTokens(except: [
            'webhook/meta',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
