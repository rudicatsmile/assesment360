<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('role.dashboard')) {
            return redirect()->to($this->dashboardPath($user->roleSlug()));
        }

        return $next($request);
    }

    private function dashboardPath(string $role): string
    {
        return (string) (config("rbac.dashboard_paths.{$role}") ?? '/fill/questionnaires');
    }
}
