<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEvaluator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowedRoles = ['guru', 'tata_usaha', 'orang_tua'];

        if (! $user || ! in_array($user->role, $allowedRoles, true)) {
            throw new AccessDeniedHttpException('Akses ditolak. Halaman ini khusus penilai.');
        }

        return $next($request);
    }
}
