<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Widgets\ClinicStatusWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\VisitasSemanalesChart;
use App\Filament\Widgets\ApexServiciosMasSolicitadosChart;
use App\Filament\Widgets\ApexEstadoVisitasChart;
use App\Filament\Widgets\ApexTiposDePacienteChart;
use App\Filament\Widgets\VisitasPorMedicoChart;
use App\Filament\Widgets\ApexVisitasPorMedicoChart;
use App\Filament\Widgets\ApexVisitasSemanalesChart;
use App\Filament\Widgets\ApexPacientesNuevosVSRecurrentes;
use App\Filament\Widgets\UltimasVisitas;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->profile()
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s') // Revisa si hay notificaciones nuevas cada 10 segundos
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::hex('#f4b857'),
                'icon' => Color::hex('#92d4ee'),
                'pink' => Color::hex('#F7CFD8'),
                'indigo' => Color::hex('#687FE5'),
            
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                \App\Filament\Pages\ReceptionDashboard::class,
                \App\Filament\Pages\SecuritySettings::class,
                // Acceso rápido: Mis visitas (solo navegación)
            ])
            ->widgets([
                // Sin widgets por defecto en el cuerpo del dashboard;
                // se mostrarán solo los de getHeaderWidgets() de ReceptionDashboard
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                'check.shift',
                'twofactor.verify',
                'restrict.doctor',
                'restrict.reception',
                'restrict.nurse',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
