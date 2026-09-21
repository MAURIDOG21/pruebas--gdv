<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class RestrictDoctorAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && ($user->role?->value === UserRole::MEDICO_GENERAL->value)) {
            $path = trim($request->path(), '/');
            if ($path === 'dashboard') {
                return redirect(\App\Filament\Resources\Appointments\AppointmentResource::getUrl('index'));
            }
            $allowedExact = [
                'dashboard',
            ];
            $allowedPrefixes = [
                'dashboard/login',
                'dashboard/logout',
                'dashboard/notifications',
                'dashboard/patients',
                'dashboard/medical-records',
                'dashboard/appointments',
                'dashboard/two-factor',
                'dashboard/profile',
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
