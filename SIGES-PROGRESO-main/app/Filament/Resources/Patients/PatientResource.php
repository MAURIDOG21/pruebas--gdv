<?php

namespace App\Filament\Resources\Patients;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Filament\Resources\Patients\Pages\ViewPatient;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Filament\Resources\Patients\Schemas\PatientInfolist;
use App\Filament\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;


use App\Filament\Resources\Patients\RelationManagers\MedicalLeavesRelationManager;
use Filament\Schemas\Components\Form;

// Importa estas clases al principio del archivo:
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;
use Filament\Schemas\Components\Tabs;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'full_name';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static ?string $modelLabel = 'Pacientes';
    protected static ?string $pluralModelLabel = 'Pacientes';
    public static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value ?? null;
        return ! in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::ENFERMERO->value], true);
    }
    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Relacionadores movidos a MedicalRecordResource
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        // Contamos cuántos pacientes tienen el estado 'pending_review'
        $count = Patient::where('status', 'pending_review')->count();
    
        // Si el contador es cero, no devolvemos nada (la insignia no aparecerá)
        // Si es mayor que cero, devolvemos el número como un string.
        return $count > 0 ? (string) $count : null;
    }
    public static function getTabs(): array
    {
        return [
            'all' => Tabs::make('Todos los Pacientes'),

            'pending_review' => Tabs::make('Pendientes de Revisión')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review'))
                ->badge(Patient::query()->where('status', 'pending_review')->count())
                ->badgeColor('warning'),

            'active' => Tabs::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
        ];
    }
        public static function getNavigationSort(): ?int
    {
    return 4;
    }
}
