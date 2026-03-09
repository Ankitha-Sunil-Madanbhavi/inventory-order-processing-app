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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (
            \App\Exceptions\InsufficientStockException $e,
            \Illuminate\Http\Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->getStockErrors(),
            ], 422);
        });

        $exceptions->render(function (
            \App\Exceptions\OrderCancellationException $e,
            \Illuminate\Http\Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

    })->create();