<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/seller.php'));
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureNotSuspended::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'is.admin' => \App\Http\Middleware\IsAdmin::class,
            'is.approved.seller' => \App\Http\Middleware\IsApprovedSeller::class,
            'is.supplier' => \App\Http\Middleware\IsSupplier::class,
            'branch.access' => \App\Http\Middleware\BranchAccess::class,
            'branch.seller' => \App\Http\Middleware\RedirectBranchUserToSeller::class,
            'not.suspended' => \App\Http\Middleware\EnsureNotSuspended::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
