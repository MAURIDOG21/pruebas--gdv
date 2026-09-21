<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireTwoFactorVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $hasTwoFactor = filled($user->two_factor_secret) && !is_null($user->two_factor_confirmed_at);
        $passed = session('two_factor_passed') === true;

        if ($hasTwoFactor && !$passed) {
            $path = $request->path();
            $allowed = [
                'dashboard/login',
                'dashboard/two-factor',
                'dashboard/security',
            ];
            foreach ($allowed as $allow) {
                if (str_starts_with($path, $allow)) {
                    return $next($request);
                }
            }
            return redirect('/dashboard/two-factor');
        }

        return $next($request);
    }
}