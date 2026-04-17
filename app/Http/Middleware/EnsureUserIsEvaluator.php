<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserIsEvaluator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isEvaluatorRole()) {
            throw new AccessDeniedHttpException('Akses ditolak. Halaman ini khusus penilai.');
        }

        return $next($request);
    }
}
