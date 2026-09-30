<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'approved' => \App\Http\Middleware\EnsureUserIsApproved::class,
            'admin'    => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error 419 (token/sesi kedaluwarsa) -> kembali ke login dengan pesan yang jelas
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return redirect()->route('login')->withErrors([
                    'login' => 'Sesi Anda sudah berakhir. Silakan masuk kembali.',
                ]);
            }
        });
    })->create();