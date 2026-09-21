<?php

namespace App\Filament\Resources\MedicalRecords;

use App\Filament\Resources\MedicalRecords\Pages\CreateMedicalRecord;
use App\Filament\Resources\MedicalRecords\Pages\EditMedicalRecord;
use App\Filament\Resources\MedicalRecords\Pages\ListMedicalRecords;
use App\Filament\Resources\MedicalRecords\Pages\ViewMedicalRecord;
use App\Filament\Resources\MedicalRecords\Schemas\MedicalRecordForm;
use App\Filament\Resources\MedicalRecords\Schemas\MedicalRecordInfolist;
use App\Filament\Resources\MedicalRecords\Tables\MedicalRecordsTable;
use App\Models\MedicalRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

use App\Filament\Resources\MedicalRecords\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\MedicalRecords\RelationManagers\MedicalDocumentsRelationManager;
use App\Filament\Resources\MedicalRecords\RelationManagers\MedicalLeavesRelationManager;
use App\Filament\Resources\MedicalRecords\RelationManagers\SomatometricReadingsRelationManager;
use App\Filament\Resources\MedicalRecords\RelationManagers\NursingAssessmentRelationManager;
use App\Filament\Resources\MedicalRecords\RelationManagers\PrescriptionsRelationManager;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-folder';
    protected static ?string $recordTitleAttribute = 'record_number';
    protected static ?string $navigationLabel = 'Expedientes';
    protected static ?string $modelLabel = 'Expediente';
    protected static ?string $pluralModelLabel = 'Expedientes';
    public static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value ?? null;
        return ! in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value, UserRole::ENFERMERO->value], true);
    }
    public static function form(Schema $schema): Schema
    {
        return MedicalRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MedicalRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicalRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
       // ¡Aquí conectamos todos los historiales clínicos al expediente!
        return [
            AppointmentsRelationManager::class,
            MedicalLeavesRelationManager::class,
            SomatometricReadingsRelationManager::class,
            MedicalDocumentsRelationManager::class,
            //NursingAssessmentRelationManager::class,
            PrescriptionsRelationManager::class,
            \App\Filament\Resources\MedicalRecords\RelationManagers\ActivityLogRelationManager::class,
            \App\Filament\Resources\MedicalRecords\RelationManagers\NursingAssessmentInitialRelationManager::class,
            \App\Filament\Resources\MedicalRecords\RelationManagers\MedicalInitialAssessmentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicalRecords::route('/'),
            'create' => CreateMedicalRecord::route('/create'),
            'view' => ViewMedicalRecord::route('/{record}'),
            'edit' => EditMedicalRecord::route('/{record}/edit'),
        ];
    }
        public static function getNavigationSort(): ?int
    {
    return 3;
    }
}
