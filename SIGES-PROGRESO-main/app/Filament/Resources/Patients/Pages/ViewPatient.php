<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
//SOMATOMETRICO
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth; // <-- PASO 1: Importa el Facade
use Filament\Schemas\Components\Fieldset as ComponentsFieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Editar Paciente')->icon('heroicon-o-pencil')->tooltip('Editar información del paciente'),
            /*Action::make('addSomatometricReading')
                ->label('Registrar Somatometría')
                ->icon('heroicon-o-heart')
                ->tooltip('Registrar somatometría del paciente')
                ->schema([
                    ComponentsFieldset::make('Presión Arterial')
                        ->schema([
                            TextInput::make('blood_pressure_systolic')
                                ->label('Sistólica')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(300)
                                ->integer(),
                            TextInput::make('blood_pressure_diastolic')
                                ->label('Diastólica')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(200)
                                ->integer(),
                        ])->columns(2),
                    ComponentsFieldset::make('Signos Vitales')
                        ->schema([
                            TextInput::make('heart_rate')
                                ->label('Frecuencia Cardíaca (ppm)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(300)
                                ->integer(),
                            TextInput::make('temperature')
                                ->label('Temperatura (°C)')
                                ->numeric()
                                ->minValue(30)
                                ->maxValue(45)
                                ->step(0.1),
                        ])->columns(2),
                    ComponentsFieldset::make('Medidas Corporales')
                        ->schema([
                            TextInput::make('weight')
                                ->label('Peso (kg)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(500)
                                ->step(0.01),
                            TextInput::make('height')
                                ->label('Altura (m)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(3)
                                ->step(0.01),
                        ])->columns(2),
                    Textarea::make('observations')->label('Observaciones')->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    // El $this->getRecord() obtiene el paciente actual de la página
                    $patient = $this->getRecord();

                    // Asocia la lectura con el paciente y el usuario logueado
                    $patient->somatometricReadings()->create([
                        'user_id' => Auth::id(), // También es buena práctica usar Auth::id() aquí
                        ...$data, // El resto de los datos del formulario
                    ]);

                    Notification::make()
                        ->title('Lectura guardada correctamente')
                        ->success()
                        ->send();
                }),
        */];
    }
    public function getTitle(): string
    {
        // La variable $this->record contiene la visita que se está viendo.
        // Construimos un título más descriptivo.
        return "Detalles de {$this->getRecord()->full_name}";
    }
}
