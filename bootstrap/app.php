<?php

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

        // ─────────────────────────────────────────
        // Dynamic Guest Authentication Redirects
        // ─────────────────────────────────────────
        $middleware->redirectGuestsTo(function (Request $request) {
            // Isolate admin unauthorized endpoints from user authentication spaces
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            
            // Standard fallback path for customers
            return route('users.login');
        });

        // ─────────────────────────────────────────
        // CSRF Protection (Stripe safe exception)
        // ─────────────────────────────────────────
        $middleware->validateCsrfTokens(except: [
            'booking/stripe/webhook',
        ]);

        // ─────────────────────────────────────────
        // YOUR CUSTOM MIDDLEWARE ALIASES GO HERE
        // (we will add staff.role later safely)
        // ─────────────────────────────────────────
        $middleware->alias([
            'staff.role' => \App\Http\Middleware\ResolveStaffRole::class,
        ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();