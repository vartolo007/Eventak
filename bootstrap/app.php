<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsCustomer;
use App\Http\Middleware\IsVendor;
use App\Http\Middleware\IsVenueOwner;
use App\Http\Middleware\SetLocaleFromHeader;
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
        // ملاحظة مهمة: نستخدم append وليس use.
        // الدالة use() تستبدل الـ global middleware stack بالكامل، وهذا كان يحذف
        // HandleCors و TrustProxies و ConvertEmptyStringsToNull ... من التطبيق.
        $middleware->append(SetLocaleFromHeader::class);

        $middleware->alias([
            'isAdmin' => IsAdmin::class,
            'isVenueOwner' => IsVenueOwner::class,
            'isCustomer' => IsCustomer::class,
            'isVendor' => IsVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
