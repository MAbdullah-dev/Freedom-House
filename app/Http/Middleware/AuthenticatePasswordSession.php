<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePasswordSession
{
    /**
     * Reject sessions whose password hash no longer matches the signed-in user.
     * A session that now belongs to a different user gets a fresh hash instead.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null) {
            $guard = (string) auth()->getDefaultDriver();
            $userKey = 'password_hash_user_'.$guard;
            $storedUser = $request->session()->get($userKey);
            if ($storedUser !== null && (int) $storedUser !== (int) $user->getAuthIdentifier()) {
                $request->session()->forget('password_hash_'.$guard);
            }
            $request->session()->put($userKey, $user->getAuthIdentifier());
        }

        try {
            return app(AuthenticateSession::class)->handle($request, $next);
        } catch (AuthenticationException) {
            return redirect()->guest(route('login'));
        }
    }
}
