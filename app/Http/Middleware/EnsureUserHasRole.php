<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($slugs === []) {
            return $next($request);
        }

        abort_unless($user->hasAnyRoleSlug($slugs), 403);

        return $next($request);
    }
}

