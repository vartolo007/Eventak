<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SetLocaleFromHeader;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([SetLocaleFromHeader::class]);

        $middleware->alias([
            'isAdmin' => IsAdmin::class,
            'isVenueOwner' => \App\Http\Middleware\IsVenueOwner::class,
            'isCustomer' => \App\Http\Middleware\IsCustomer::class,
            'isVendor' => \App\Http\Middleware\IsVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
