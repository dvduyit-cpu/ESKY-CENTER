<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'permission' => EnsurePermission::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception, \Illuminate\Http\Request $request) {
            if ($exception->getStatusCode() !== 422 || $request->expectsJson()) return null;

            return back()->with('warning', $exception->getMessage() ?: 'Thao tác không thể thực hiện ở trạng thái hiện tại.');
        });
    })->create();
