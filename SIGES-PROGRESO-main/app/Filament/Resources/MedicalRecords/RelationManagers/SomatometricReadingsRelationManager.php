<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

class SomatometricReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'somatometricReadings';
    protected static ?string $title = 'Notas de Evolución Somatométrica';

    public static function getFormSchema(): array
    {
        return [
            TextInput::make('blood_pressure_systolic')->label('Presión Sistólica (mmHg)')->numeric()->minValue(60)->maxValue(250)->step('1'),
            TextInput::make('blood_pressure_diastolic')->label('Presión Diastólica (mmHg)')->numeric()->minValue(40)->maxValue(150)->step('1'),
            TextInput::make('heart_rate')->label('Frecuencia Cardíaca (lpm)')->numeric()->minValue(30)->maxValue(220)->step('1'),
            TextInput::make('temperature')->label('Temperatura (°C)')->numeric()->minValue(25)->maxValue(45)->step('0.1'),
            TextInput::make('respiratory_rate')->label('Frecuencia Respiratoria (rpm)')->numeric()->minValue(5)->maxValue(60)->step('1'),
            TextInput::make('oxygen_saturation')->label('SpO₂ (%)')->numeric()->minValue(50)->maxValue(100)->step('1'),
            TextInput::make('weight')->label('Peso (kg)')->numeric()->minValue(2)->maxValue(400)->step('0.1'),
            TextInput::make('height_cm')->label('Talla (cm)')->numeric()->minValue(30)->maxValue(250)->step('0.1'),
            TextInput::make('blood_glucose')->label('Glucosa (mg/dL)')->numeric()->minValue(20)->maxValue(600)->step('1'),
            Textarea::make('observations')->label('Nota de evolución')->columnSpanFull(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('blood_pressure_systolic')->label('Presión Sistólica (mmHg)')->numeric()->minValue(60)->maxValue(250)->step('1'),
                TextInput::make('blood_pressure_diastolic')->label('Presión Diastólica (mmHg)')->numeric()->minValue(40)->maxValue(150)->step('1'),
                TextInput::make('heart_rate')->label('Frecuencia Cardíaca (lpm)')->numeric()->minValue(30)->maxValue(220)->step('1'),
                TextInput::make('temperature')->label('Temperatura (°C)')->numeric()->minValue(25)->maxValue(45)->step('0.1'),
                TextInput::make('respiratory_rate')->label('Frecuencia Respiratoria (rpm)')->numeric()->minValue(5)->maxValue(60)->step('1'),
                TextInput::make('oxygen_saturation')->label('SpO₂ (%)')->numeric()->minValue(50)->maxValue(100)->step('1'),
                TextInput::make('weight')->label('Peso (kg)')->numeric()->minValue(2)->maxValue(400)->step('0.1'),
                TextInput::make('height_cm')->label('Talla (cm)')->numeric()->minValue(30)->maxValue(250)->step('0.1'),
                TextInput::make('blood_glucose')->label('Glucosa (mg/dL)')->numeric()->minValue(20)->maxValue(600)->step('1'),
                Textarea::make('observations')->label('Nota de evolución')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ticket_number')
            ->modifyQueryUsing(function (Builder $query) {
                return $query;
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha de Toma')
                    ->dateTime('d/m/Y H:i a')
                    ->sortable(),
                TextColumn::make('blood_pressure')
                    ->label('Presión Arterial')
                    ->getStateUsing(fn ($record) => $record->blood_pressure_systolic . '/' . $record->blood_pressure_diastolic . ' mmHg'),
                TextColumn::make('weight')
                    ->label('Peso')
                    ->suffix(' kg'),
                TextColumn::make('height_cm')
                    ->label('Talla')
                    ->getStateUsing(fn ($record) => $record->height !== null ? number_format($record->height * 100, 1) . ' cm' : '—'),
                TextColumn::make('temperature')
                    ->label('Temp')
                    ->suffix(' °C'),
                TextColumn::make('heart_rate')
                    ->label('FC')
                    ->suffix(' lpm'),
                TextColumn::make('respiratory_rate')
                    ->label('FR')
                    ->suffix(' rpm'),
                TextColumn::make('oxygen_saturation')
                    ->label('SpO₂')
                    ->suffix(' %'),
                TextColumn::make('blood_glucose')
                    ->label('Glucosa')
                    ->suffix(' mg/dL'),
                TextColumn::make('observations')
                    ->label('Nota')
                    ->limit(40),
                TextColumn::make('user.name')->label('Registrado por'),
            ])
            ->headerActions([
                CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = Auth::id(); // Asigna al usuario actual
                    if (isset($data['height_cm'])) {
                        $data['height'] = is_numeric($data['height_cm']) ? ($data['height_cm'] / 100) : null;
                        unset($data['height_cm']);
                    }
                    return $data;
                }),
            ])

            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Detalle de Nota Somatométrica')
                    ->infolist(function (Schema $schema) {
                        return $schema->schema([
                            Section::make('Signos vitales')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->schema([
                                    TextEntry::make('created_at')
                                        ->label('Fecha de Toma')
                                        ->state(fn ($record) => $record->created_at ? $record->created_at->format('d/m/Y H:i a') : '—'),
                                    TextEntry::make('blood_pressure')
                                        ->label('Presión Arterial (mmHg)')
                                        ->state(fn ($record) => ($record->blood_pressure_systolic ?? '—') . '/' . ($record->blood_pressure_diastolic ?? '—')),
                                    TextEntry::make('heart_rate')
                                        ->label('Frecuencia Cardíaca (lpm)')
                                        ->state(fn ($record) => $record->heart_rate ?? '—'),
                                    TextEntry::make('respiratory_rate')
                                        ->label('Frecuencia Respiratoria (rpm)')
                                        ->state(fn ($record) => $record->respiratory_rate ?? '—'),
                                    TextEntry::make('temperature')
                                        ->label('Temperatura (°C)')
                                        ->state(fn ($record) => $record->temperature ?? '—'),
                                    TextEntry::make('oxygen_saturation')
                                        ->label('SpO₂ (%)')
                                        ->state(fn ($record) => $record->oxygen_saturation ?? '—'),
                                    TextEntry::make('weight')
                                        ->label('Peso (kg)')
                                        ->state(fn ($record) => $record->weight ?? '—'),
                                    TextEntry::make('height_cm')
                                        ->label('Talla (cm)')
                                        ->state(fn ($record) => isset($record->height) ? number_format($record->height * 100, 1) : '—'),
                                    TextEntry::make('blood_glucose')
                                        ->label('Glucosa (mg/dL)')
                                        ->state(fn ($record) => $record->blood_glucose ?? '—'),
                                ]),
                            Section::make('Nota de evolución')
                                ->schema([
                                    TextEntry::make('observations')
                                        ->label('Nota')
                                        ->state(fn ($record) => $record->observations ?? '—'),
                                    TextEntry::make('user_name')
                                        ->label('Registrado por')
                                        ->state(fn ($record) => $record->user ? $record->user->name : '—'),
                                ]),
                        ]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}