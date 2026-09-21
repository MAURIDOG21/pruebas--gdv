<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Enums\PatientType;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Pages\HelpCenter; // Importar la página de ayuda

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // --- NUEVA ACCIÓN DE DESCARGA ---
            Action::make('download_consent_template')
                ->label('Descargar Plantilla de Consentimiento')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(asset('storage/templates/consentimiento_plantilla.pdf'))
                ->openUrlInNewTab(),
           // Botón de Ayuda Contextual
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->url(HelpCenter::getUrl() . '#pacientes') // Redirige a la sección específica
                ->openUrlInNewTab(), // Opcional: abrir en nueva pestaña para no perder el trabajo

            //CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // Mapeos de colores e íconos para tipos y estados
        $typeMeta = [
            PatientType::EXTERNAL->value => ['color' => 'gray', 'icon' => 'heroicon-o-globe-alt'],
            PatientType::EMPLOYEE->value => ['color' => 'primary', 'icon' => 'heroicon-o-briefcase'],
            PatientType::EMPLOYEE_DEPENDENT->value => ['color' => 'warning', 'icon' => 'heroicon-o-users'],
            PatientType::PEDIATRIC->value => ['color' => 'success', 'icon' => 'heroicon-o-user'],
        ];

        $statusMeta = [
            'active' => ['label' => 'Activos', 'color' => 'success', 'icon' => 'heroicon-o-check-circle'],
            'pending_review' => ['label' => 'Pendientes de Revisión', 'color' => 'warning', 'icon' => 'heroicon-o-clock'],
        ];

        $tabs = [
            'all' => Tab::make('Todos los Pacientes')
                ->icon('heroicon-o-users')
                ->badge(PatientResource::getModel()::query()->count())
                ->badgeColor('gray'),
        ];

        // Tabs por Tipo
        foreach (PatientType::cases() as $type) {
            $count = PatientResource::getModel()::query()
                ->whereHas('medicalRecord', fn (Builder $q) => $q->where('patient_type', $type))
                ->count();

            $tabs[$type->value] = Tab::make($type->value)
                ->icon($typeMeta[$type->value]['icon'])
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereHas('medicalRecord', fn (Builder $q) => $q->where('patient_type', $type))
                )
                ->badge($count)
                ->badgeColor($typeMeta[$type->value]['color']);
        }

        // Tabs por Estado
        foreach ($statusMeta as $status => $meta) {
            $count = PatientResource::getModel()::query()->where('status', $status)->count();

            $tabs['status_' . $status] = Tab::make($meta['label'])
                ->icon($meta['icon'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status))
                ->badge($count)
                ->badgeColor($meta['color']);
        }
        return $tabs;
    }
}
