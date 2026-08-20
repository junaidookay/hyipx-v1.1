<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle($request, Closure $next, $guard = null)
    {

        if (Auth::guard($guard)->check()) {
            \Log::info('[DEBUG-GUEST] RedirectIfAuthenticated: user IS authenticated, redirecting to user.home', [
                'url' => $request->fullUrl(),
                'user_id' => Auth::guard($guard)->id(),
            ]);
            return to_route('user.home');
        }

        \Log::info('[DEBUG-GUEST] RedirectIfAuthenticated: user NOT authenticated, passing through', [
            'url' => $request->fullUrl(),
        ]);
        return $next($request);

    }
}
