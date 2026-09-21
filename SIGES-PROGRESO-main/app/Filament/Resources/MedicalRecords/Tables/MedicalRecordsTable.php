<?php

namespace App\Filament\Resources\MedicalRecords\Tables;

use App\Enums\PatientType;
use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MedicalRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('record_number')
                            ->label('N° de Expediente')
                            ->searchable()
                            ->weight('bold')
                            ->size('lg'),
                        // Mostramos el nombre del paciente a través de la relación
                        TextColumn::make('patient.full_name')
                            ->label('Paciente')
                            ->searchable()
                            ->icon('heroicon-m-user')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                    Stack::make([
                        BadgeColumn::make('patient_type')
                            ->label('Tipo de Paciente')
                            ->formatStateUsing(fn ($state) => $state ? ($state instanceof PatientType ? $state->value : (string) $state) : 'Sin tipo')
                            ->colors([
                                'info' => static fn ($state): bool => in_array($state, [PatientType::EXTERNAL]),
                                'success' => static fn ($state): bool => in_array($state, [PatientType::EMPLOYEE]),
                                'warning' => static fn ($state): bool => in_array($state, [PatientType::EMPLOYEE_DEPENDENT]),
                                'primary' => static fn ($state): bool => in_array($state, [PatientType::PEDIATRIC]),
                                'gray' => static fn ($state): bool => $state === null,
                            ]),
                        TextColumn::make('created_at')
                            ->label('Creación')
                            ->dateTime('d/m/Y')
                            ->sortable()
                            ->icon('heroicon-m-calendar')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                //EditAction::make(),
                DeleteAction::make()
                    ->visible(fn () => in_array(Auth::user()?->role?->value, [UserRole::ADMIN->value, UserRole::DIRECTOR->value], true))
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Contraseña actual')
                            ->password()
                            ->rule('current_password')
                            ->required(),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => in_array(Auth::user()?->role?->value, [UserRole::ADMIN->value, UserRole::DIRECTOR->value], true)),
                ]),
            ]);
    }
}
