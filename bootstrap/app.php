<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
<<<<<<< HEAD
        //
    })
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
=======
        // Register role middleware alias used throughout route files.
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 403 — Access Denied: show a branded page with the user's role,
        // the attempted URL, and a link to their role-appropriate home.
        $exceptions->render(
            function (
                \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e,
                \Illuminate\Http\Request $request
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Forbidden. You do not have permission to perform this action.',
                        'role'    => $request->user()?->role,
                    ], 403);
                }

                return response()->view('errors.403', [], 403);
            }
        );

        // 404 — Not Found: show a branded page with a link back to the
        // user's home page (or login if unauthenticated).
        $exceptions->render(
            function (
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
                \Illuminate\Http\Request $request
            ) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Not found.'], 404);
                }

                return response()->view('errors.404', [], 404);
            }
        );

>>>>>>> 9ad783d (Initial commit)
    })->create();
