<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->guest(route('login'));
        }

        $allowed = [];

        foreach ($roles as $role) {
            $role = trim($role);

            if ($role !== '') {
                $allowed[] = $role;
            }
        }

        $roleName = $user->role?->role_name;

        if ($allowed === [] || ! is_string($roleName) || ! in_array($roleName, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
