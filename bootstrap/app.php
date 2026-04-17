<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$rbac = require __DIR__ . '/../config/rbac.php';
$middlewareAliases = (array) ($rbac['middleware_aliases'] ?? []);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) use ($middlewareAliases): void {
        $adminAlias = (string) ($middlewareAliases['admin_gate'] ?? 'access.admin');
        $evaluatorAlias = (string) ($middlewareAliases['evaluator_gate'] ?? 'access.evaluator');
        $roleAlias = (string) ($middlewareAliases['role_gate'] ?? 'access.role');
        $roleRedirectAlias = (string) ($middlewareAliases['role_redirect'] ?? 'access.role.redirect');

        $middleware->alias([
            $adminAlias => \App\Http\Middleware\EnsureUserIsAdmin::class,
            $evaluatorAlias => \App\Http\Middleware\EnsureUserIsEvaluator::class,
            $roleAlias => \App\Http\Middleware\EnsureUserHasRole::class,
            $roleRedirectAlias => \App\Http\Middleware\RedirectByRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
