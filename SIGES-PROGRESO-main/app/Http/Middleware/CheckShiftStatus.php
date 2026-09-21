<?php

namespace App\Http\Middleware;

use App\Models\ClinicSchedule;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckShiftStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar el middleware si el usuario está autenticado
        if (!Auth::check()) {
            return $next($request);
        }

        // Lista de rutas que requieren turno abierto
        $protectedRoutes = [
            'filament.admin.resources.appointments.*',
            'filament.admin.resources.clinic-schedules.*',
            'filament.admin.pages.reception-dashboard',
        ];

        // Verificar si la ruta actual está protegida
        $currentRoute = $request->route()->getName();
        $isProtectedRoute = false;

        foreach ($protectedRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                $isProtectedRoute = true;
                break;
            }
        }

        // Si no es una ruta protegida, continuar
        if (!$isProtectedRoute) {
            return $next($request);
        }

        // Verificar si hay algún turno abierto para hoy
        $today = now()->toDateString();
        $openShift = ClinicSchedule::where('date', $today)
            ->where('is_shift_open', true)
            ->first();

        // Si no hay turno abierto, verificar si hay turnos disponibles para abrir
        if (!$openShift) {
            $availableShifts = ClinicSchedule::where('date', $today)
                ->where('is_active', true)
                ->where('is_shift_open', false)
                ->get();

            if ($availableShifts->isNotEmpty()) {
                // Redirigir a la vista consolidada dentro del recurso ClinicSchedules (Horario del Día)
                session()->flash('shift_required', 'Debe abrir un turno antes de continuar.');
                session()->put('available_shifts', $availableShifts->toArray());
                session()->put('intended_url', $request->fullUrl());

                // Redirigir al recurso: clinic-schedules/day en el panel 'dashboard'
                return redirect()->route('filament.dashboard.resources.clinic-schedules.day');
            } else {
                // No hay turnos disponibles para hoy
                session()->flash('no_shifts_available', 'No hay turnos programados para hoy.');
                return redirect()->route('filament.admin.pages.reception-dashboard');
            }
        }

        return $next($request);
    }
}