<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class RestrictReceptionAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && ($user->role?->value === UserRole::RECEPCIONISTA->value)) {
            $path = trim($request->path(), '/');
            $allowedExact = ['dashboard'];
            $allowedPrefixes = [
                'dashboard/login',
                'dashboard/logout',
                'dashboard/profile',
                'dashboard/two-factor',
                'dashboard/notifications',
                'dashboard/patients',
                'dashboard/clinic-schedules',
                'dashboard/appointments',
                'dashboard/ayuda',
            ];

            $allowed = in_array($path, $allowedExact, true);
            if (! $allowed) {
                foreach ($allowedPrefixes as $prefix) {
                    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                        $allowed = true;
                        break;
                    }
                }
            }

            if (! $allowed) {
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}
