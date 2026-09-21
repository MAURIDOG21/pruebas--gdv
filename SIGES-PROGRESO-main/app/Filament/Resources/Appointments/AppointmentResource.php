<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\AppointmentStatus; // Importar el Enum de estados
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
class AppointmentResource extends Resource
{
    
    protected static ?string $model = Appointment::class;

   // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $recordTitleAttribute = 'ticket_number'; //paciente nombre o numero de ticket
    protected static ?string $navigationLabel = 'Visitas';
    protected static ?string $modelLabel = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas';

        /**
     * Devuelve el número para la insignia de navegación.
     */
    public static function getNavigationBadge(): ?string
    {
        // Contamos cuántas citas/visitas tienen el estado 'pending'
        $count = static::getModel()::where('status', AppointmentStatus::PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

        /**
     * Devuelve el color para la insignia de navegación.
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        // Si hay visitas pendientes, el color será 'info' (azul).
        return static::getNavigationBadge() ? 'info' : 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        $user = Auth::user();
        if ($user && $user->role === UserRole::RECEPCIONISTA) {
            return [];
        }
        return [
            \App\Filament\Resources\Appointments\RelationManagers\NursingEvolutionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
    public static function getNavigationSort(): ?int
    {
    return 2;
    }
}
