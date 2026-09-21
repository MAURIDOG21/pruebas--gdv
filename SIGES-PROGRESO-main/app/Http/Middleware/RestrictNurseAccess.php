<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class RestrictNurseAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && ($user->role?->value === UserRole::ENFERMERO->value)) {
            $path = trim($request->path(), '/');
            if ($path === 'dashboard') {
                return redirect(\App\Filament\Resources\Appointments\AppointmentResource::getUrl('index'));
            }
            $allowedExact = [];
            $allowedPrefixes = [
                'dashboard/login',
                'dashboard/logout',
                'dashboard/profile',
                'dashboard/two-factor',
                'dashboard/notifications',
                'dashboard/appointments',
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
                abort(403);
            }
        }

        return $next($request);
    }
}

