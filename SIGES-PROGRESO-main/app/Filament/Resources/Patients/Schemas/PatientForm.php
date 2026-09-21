<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema; 
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Section;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use Filament\Actions\Action;
// --- Importar todos los Enums ---
use App\Enums\Locality;
use Carbon\Carbon;
use App\Enums\PatientType;
use App\Enums\EmployeeStatus;
use App\Enums\ChronicDisease;
use App\Support\ColoniaCatalog;

use Filament\Forms\Components\FileUpload; // <-- Importar FileUpload
use Illuminate\Database\Eloquent\Model; // <-- Importar Model

class PatientForm
{
    /**
     * Devuelve los componentes básicos del formulario de paciente para reutilización
     * 
     * @return array
     */
    /**
     * Devuelve los componentes del formulario de paciente para reutilización
     * 
     * @return array
     */
    public static function getBasicPatientFormSchema(): array
    {
        // Esquema completo para el formulario principal
        return [
            Tabs::make('Información del Paciente')
                    ->tabs([
                        // Pestaña 1: Información Personal
                        Tab::make('Información Personal')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                DatePicker::make('date_of_birth')
                                    ->label('Fecha de Nacimiento')
                                    ->required()
                                    ->native(false)
                                    ->maxDate(now())
                                    ->minDate(now()->subYear(120))
                                    ->displayFormat('d/F/Y')
                                    ->locale('es')
                                    ->prefixIcon('heroicon-s-calendar-date-range')
                                    ->prefixIconColor('icon')
                                    ->live()
                                    ->afterStateHydrated(function ($get, $set) {
                                        $dateOfBirth = $get('date_of_birth');
                                        if ($dateOfBirth) {
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                        }
                                    })
                                    ->afterStateUpdated(function ($get, $set) {
                                        $dateOfBirth = $get('date_of_birth');
                                        if ($dateOfBirth) {
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                        } else {
                                            $set('age_display', null);
                                        }
                                    }),

                                Select::make('sex')
                                    ->label('Sexo')
                                    ->placeholder('Selecciona una opción')
                                    ->options([
                                        'M' => 'Masculino',
                                        'F' => 'Femenino'
                                    ])
                                    ->required(),

                                TextInput::make('curp')
                                    ->label('CURP')
                                    ->extraAttributes([
                                        'x-mask' => 'AAAA999999HAA999A9',
                                        '@input' => '$event.target.value = $event.target.value.toUpperCase()',
                                    ])
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null)
                                    ->unique(ignoreRecord: true)
                                    ->regex('/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|CL|CM|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS){1}[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/')
                                    ->validationAttribute('CURP')
                                    ->live(debounce: '500ms')
                                    ->afterStateUpdated(function ($get, $set) {
                                        $curp = $get('curp');
                                        if (strlen($curp) === 18) {
                                            $dateStr = substr($curp, 4, 6);
                                            $year = substr($dateStr, 0, 2);
                                            $month = substr($dateStr, 2, 2);
                                            $day = substr($dateStr, 4, 2);
                                            $century = (intval($year) > (int)date('y')) ? '19' : '20';
                                            $fullYear = $century . $year;
                                            $dateOfBirth = "{$fullYear}-{$month}-{$day}";
                                            $set('date_of_birth', $dateOfBirth);
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                            $set('sex', (substr($curp, 10, 1) === 'H') ? 'M' : 'F');
                                        } else {
                                            // Si el CURP se borra o es inválido, limpiamos los campos.
                                            $set('date_of_birth', null);
                                            $set('sex', null);
                                            $set('age_display', null);
                                        }
                                    }),

                                TextInput::make('age_display')
                                    ->label('Edad')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se calculará automáticamente'),
                            ])
                            ->columns(2),

                        // Pestaña 2: Contacto y Residencia
                        Tab::make('Contacto y Residencia')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Select::make('locality')
                                    ->label('Localidad')
                                    ->options(fn () => class_exists(\App\Enums\Locality::class) ? \App\Enums\Locality::getOptions() : [])
                                    ->searchable()
                                    ->searchPrompt('Empieza a escribir para buscar...')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ( $set) => $set('colonia', null)),

                                Select::make('colonia')
                                    ->label('Colonia')
                                    ->options(fn ( $get) => ColoniaCatalog::getColonias($get('locality')))
                                    ->disabled(fn ( $get) => ! $get('locality'))
                                    ->searchable(),

                                TextInput::make('contact_phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->maxLength(15),

                                Textarea::make('address')
                                    ->label('Dirección Completa')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Pestaña 3: Expediente y Documentos
                        Tab::make('Expediente y Documentos')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                // Campos para el tipo de paciente directamente en el formulario
                                // en lugar de usar la relación medicalRecord
                                TextInput::make('medicalRecord.record_number')
                                    ->label('Número de Expediente')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se generará automáticamente')
                                    ->columnSpanFull(),

                                Section::make('Clasificación del Expediente')
                                    ->schema([
                                        Select::make('medicalRecord.patient_type')
                                            ->label('Tipo de Paciente')
                                            ->options(PatientType::getOptions())
                                            ->required()
                                            ->live(),

                                        Select::make('medicalRecord.employee_status')
                                            ->label('Estatus de Empleado')
                                            ->options(EmployeeStatus::getOptions())
                                            ->visible(fn ($get) => $get('medicalRecord.patient_type') === PatientType::EMPLOYEE->value),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Antecedentes / Comorbilidades')
                                    ->schema([
                                        CheckboxList::make('chronic_diseases')
                                            ->label('Enfermedades Crónicas / Comorbilidades')
                                            ->options(function () {
                                                $options = [];
                                                foreach (\App\Enums\ChronicDisease::cases() as $case) {
                                                    $options[$case->value] = $case->getLabel();
                                                }
                                                return $options;
                                            })
                                            ->columns(2),
                                    ])
                                    ->columns(2),
                                // Sección para asignar tutor (solo visible para pacientes pediátricos)
                                // Esta sección solo se mostrará en el formulario principal, no en el createOptionForm
                                Section::make('Asignación de Tutor')
                                    ->schema([
                                        Select::make('tutor_id')
                                            ->label('Asignar Tutor')
                                            ->options(function () {
                                                return \App\Models\Tutor::pluck('full_name', 'id')->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('full_name')->label('Nombre Completo')->required(),
                                                TextInput::make('relationship')->label('Parentesco')->required(),
                                                TextInput::make('phone_number')->label('Teléfono'),
                                                Textarea::make('address')->label('Dirección')->columnSpanFull(),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                return \App\Models\Tutor::create($data)->id;
                                            })
                                            // Configuración simple y directa para asegurar que se guarde correctamente
                                            ->dehydrated(true)
                                    ])
                                    ->visible(fn ($get) => $get('medicalRecord.patient_type') === PatientType::PEDIATRIC->value)
                                    ->columns(2),   
                                             
                                AdvancedFileUpload::make('medicalRecord.consent_form_path')
                                    ->label('Documento de Consentimiento Informado')
                                    ->disk('public')
                                    ->directory('consent_forms')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(2048)
                                    ->downloadable()
                                    ->helperText('Subir el documento PDF escaneado y firmado por el paciente.')
                                    ->columnSpanFull(),
                                Action::make('download_consent_template')
                                        ->label('Descargar Plantilla de Consentimiento')
                                        ->icon('heroicon-o-document-arrow-down')
                                        ->color('gray')
                                        ->url(asset('storage/templates/consentimiento_plantilla.pdf'))
                                        ->openUrlInNewTab(),
                            ])
                            
                            
                                
                    ])
                    ->columnSpanFull(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Información del Paciente')
                    ->tabs([
                        // Pestaña 1: Información Personal
                        Tab::make('Información Personal')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                DatePicker::make('date_of_birth')
                                    ->label('Fecha de Nacimiento')
                                    ->required()
                                    ->native(false)
                                    ->maxDate(now())
                                    ->minDate(now()->subYear(120))
                                    ->displayFormat('d/F/Y')
                                    ->locale('es')
                                    ->prefixIcon('heroicon-s-calendar-date-range')
                                    ->prefixIconColor('icon')
                                    ->live()
                                    ->afterStateHydrated(function ($get, $set) {
                                        $dateOfBirth = $get('date_of_birth');
                                        if ($dateOfBirth) {
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                        }
                                    })
                                    ->afterStateUpdated(function ($get, $set) {
                                        $dateOfBirth = $get('date_of_birth');
                                        if ($dateOfBirth) {
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                        } else {
                                            $set('age_display', null);
                                        }
                                    }),

                                Select::make('sex')
                                    ->label('Sexo')
                                    ->placeholder('Selecciona una opción')
                                    ->options([
                                        'M' => 'Masculino',
                                        'F' => 'Femenino'
                                    ])
                                    ->required(),

                                TextInput::make('curp')
                                    ->label('CURP')
                                    ->extraAttributes([
                                        'x-mask' => 'AAAA999999HAA999A9',
                                        '@input' => '$event.target.value = $event.target.value.toUpperCase()',
                                    ])
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null)
                                    ->unique(ignoreRecord: true)
                                    ->regex('/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|CL|CM|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS){1}[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/')
                                    ->validationAttribute('CURP')
                                    ->live(debounce: '500ms')
                                    ->afterStateUpdated(function ($get, $set) {
                                        $curp = $get('curp');
                                        if (strlen($curp) === 18) {
                                            $dateStr = substr($curp, 4, 6);
                                            $year = substr($dateStr, 0, 2);
                                            $month = substr($dateStr, 2, 2);
                                            $day = substr($dateStr, 4, 2);
                                            $century = (intval($year) > (int)date('y')) ? '19' : '20';
                                            $fullYear = $century . $year;
                                            $dateOfBirth = "{$fullYear}-{$month}-{$day}";
                                            $set('date_of_birth', $dateOfBirth);
                                            $age = Carbon::parse($dateOfBirth)->age;
                                            $set('age_display', $age . ' años');
                                            $set('sex', (substr($curp, 10, 1) === 'H') ? 'M' : 'F');
                                        } else {
                                            // Si el CURP se borra o es inválido, limpiamos los campos.
                                            $set('date_of_birth', null);
                                            $set('sex', null);
                                            $set('age_display', null);
                                        }
                                    }),

                                TextInput::make('age_display')
                                    ->label('Edad')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se calculará automáticamente'),
                            ])
                            ->columns(2),

                        // Pestaña 2: Contacto y Residencia
                        Tab::make('Contacto y Residencia')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Select::make('locality')
                                    ->label('Localidad')
                                    ->options(fn () => class_exists(\App\Enums\Locality::class) ? \App\Enums\Locality::getOptions() : [])
                                    ->searchable()
                                    ->searchPrompt('Empieza a escribir para buscar...')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ( $set) => $set('colonia', null)),

                                Select::make('colonia')
                                    ->label('Colonia')
                                    ->options(fn ( $get) => ColoniaCatalog::getColonias($get('locality')))
                                    ->disabled(fn ( $get) => ! $get('locality'))
                                    ->searchable(),

                                TextInput::make('contact_phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->maxLength(15),

                                Textarea::make('address')
                                    ->label('Dirección Completa')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Pestaña 3: Expediente y Documentos
                        Tab::make('Expediente y Documentos')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('medicalRecord.record_number')
                                    ->label('Número de Expediente')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se generará automáticamente')
                                    ->columnSpanFull(),

                                Section::make('Clasificación del Expediente')
                                    ->schema([
                                        Select::make('medicalRecord.patient_type')
                                            ->label('Tipo de Paciente')
                                            ->options(PatientType::getOptions())
                                            ->required()
                                            ->live(),

                                        Select::make('medicalRecord.employee_status')
                                            ->label('Estatus de Empleado')
                                            ->options(EmployeeStatus::getOptions())
                                            ->visible(fn ($get) => $get('medicalRecord.patient_type') === PatientType::EMPLOYEE->value),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Antecedentes / Comorbilidades')
                                    ->schema([
                                        CheckboxList::make('chronic_diseases')
                                            ->label('Enfermedades Crónicas / Comorbilidades')
                                            ->options(function () {
                                                $options = [];
                                                foreach (\App\Enums\ChronicDisease::cases() as $case) {
                                                    $options[$case->value] = $case->getLabel();
                                                }
                                                return $options;
                                            })
                                            ->columns(2),
                                    ])
                                    ->columns(2),
                                
                                // Sección para asignar tutor (solo visible para pacientes pediátricos)
                                Section::make('Asignación de Tutor')
                                    ->schema([
                                        Select::make('tutor_id')
                                            ->label('Asignar Tutor')
                                            ->relationship('tutor', 'full_name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('full_name')->label('Nombre Completo')->required(),
                                                TextInput::make('relationship')->label('Parentesco')->required(),
                                                TextInput::make('phone_number')->label('Teléfono'),
                                                Textarea::make('address')->label('Dirección')->columnSpanFull(),
                                            ])
                                    ])
                                    ->visible(fn ($get) => $get('medicalRecord.patient_type') === PatientType::PEDIATRIC->value)
                                    ->columns(2),

                                AdvancedFileUpload::make('medicalRecord.consent_form_path')
                                    ->label('Documento de Consentimiento Informado')
                                    ->disk('public')
                                    ->directory('consent_forms')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(2048)
                                    ->downloadable()
                                    ->helperText('Subir el documento PDF escaneado y firmado por el paciente.')
                                    ->columnSpanFull(),
                                Action::make('download_consent_template')
                                        ->label('Descargar Plantilla de Consentimiento')
                                        ->icon('heroicon-o-document-arrow-down')
                                        ->color('gray')
                                        ->url(asset('storage/templates/consentimiento_plantilla.pdf'))
                                        ->openUrlInNewTab(),
                            ]),
                                
                    ])
                    ->columnSpanFull(),
            ]);
    }


}
