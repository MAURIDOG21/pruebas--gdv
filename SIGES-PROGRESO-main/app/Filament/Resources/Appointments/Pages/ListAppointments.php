<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use Filament\Schemas\Components\Tabs\Tab;
class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

 

    protected function getHeaderActions(): array
    {
        return [
           // CreateAction::make()->label('Crear Nueva Visita'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas las Visitas'),

            'PRIMERA_VEZ' => Tab::make('Primera Vez')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', VisitType::PRIMERA_VEZ->value))
                ->badge(AppointmentResource::getModel()::query()->where('visit_type', VisitType::PRIMERA_VEZ->value)->count())
                ->badgeColor('info'),

            'SUBSECUENTE' => Tab::make('Subsecuente')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', VisitType::SUBSECUENTE->value))
                ->badge(AppointmentResource::getModel()::query()->where('visit_type', VisitType::SUBSECUENTE->value)->count())
                ->badgeColor('gray'),

            'PENDING' => Tab::make(AppointmentStatus::PENDING->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AppointmentStatus::PENDING))
                ->badge(AppointmentResource::getModel()::query()->where('status', AppointmentStatus::PENDING)->count())
                ->badgeColor(AppointmentStatus::PENDING->getColor()),

            'IN_PROGRESS' => Tab::make(AppointmentStatus::IN_PROGRESS->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AppointmentStatus::IN_PROGRESS))
                ->badge(AppointmentResource::getModel()::query()->where('status', AppointmentStatus::IN_PROGRESS)->count())
                ->badgeColor(AppointmentStatus::IN_PROGRESS->getColor()),
        ];
    }
}
