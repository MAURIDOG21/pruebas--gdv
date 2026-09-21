<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UserRole;
use Filament\Schemas\Components\Tabs\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todo el Personal')
                ->badge(UserResource::getModel()::query()->count())
                ->badgeColor('gray'),
            
            'doctors' => Tab::make('Médicos')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [
                    UserRole::MEDICO_GENERAL->value
                ]))
                ->badge(UserResource::getModel()::query()->whereIn('role', [
                    UserRole::MEDICO_GENERAL->value
                ])->count())
                ->badgeColor('info'),
            
            'administrative' => Tab::make('Administrativos')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [
                    UserRole::ADMIN->value, 
                    UserRole::DIRECTOR->value,
                    UserRole::RECEPCIONISTA->value
                ]))
                ->badge(UserResource::getModel()::query()->whereIn('role', [
                    UserRole::ADMIN->value, 
                    UserRole::DIRECTOR->value,
                    UserRole::RECEPCIONISTA->value
                ])->count())
                ->badgeColor('danger'),
            
            'specialists' => Tab::make('Especialistas')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [
                    UserRole::NUTRICIONISTA->value, 
                    UserRole::PSICOLOGO->value
                ]))
                ->badge(UserResource::getModel()::query()->whereIn('role', [
                    UserRole::NUTRICIONISTA->value, 
                    UserRole::PSICOLOGO->value
                ])->count())
                ->badgeColor('success'),
            
            

            'support' => Tab::make('Personal de Enfermeria')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [
                    UserRole::FARMACIA->value, 
                    UserRole::ENFERMERO->value
                ]))
                ->badge(UserResource::getModel()::query()->whereIn('role', [
                    UserRole::FARMACIA->value, 
                    UserRole::ENFERMERO->value
                ])->count())
                ->badgeColor('primary'),
        ];

        return $tabs;
    }
}
