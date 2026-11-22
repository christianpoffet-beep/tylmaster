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
        $middleware->remove(\Illuminate\Http\Middleware\ValidatePostSize::class);
        // No SSH on the hosting: the first request after a deploy runs its migrations.
        $middleware->web(prepend: [\App\Http\Middleware\FinishDeploy::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Past post_max_size PHP drops the whole form, the CSRF token with it:
        // say that the file was too large instead of "Page Expired".
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) return null;

            $limit = (string) ini_get('post_max_size');
            $bytes = (int) $limit * match (strtolower(substr(trim($limit), -1))) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
            if ($bytes > 0 && (int) $request->server('CONTENT_LENGTH') > $bytes) {
                $message = 'Die Datei ist zu gross für den Server (höchstens ' . floor($bytes / 1024 ** 2) . ' MB pro Upload).';

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 413)
                    : back()->with('error', $message);
            }

            // A script that posts (the Rebeat upload) learns what was missing - none of it secret;
            // a form gets resources/views/errors/419.blade.php, which shows the same
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Der Server lehnt die Sitzung ab',
                    'diagnose' => \App\Support\SessionDiagnosis::of($request),
                ], 419);
            }

            return null;
        });
    })->create();
