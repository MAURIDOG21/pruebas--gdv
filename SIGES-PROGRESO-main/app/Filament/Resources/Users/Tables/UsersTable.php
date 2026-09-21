<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;

class UsersTable
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
                        TextColumn::make('name')
                            ->label('Nombre')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->size('lg'),
                            
                        TextColumn::make('email')
                            ->label('Correo electrónico')
                            ->searchable()
                            ->icon('heroicon-m-envelope')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                    
                    Stack::make([
                        BadgeColumn::make('role')
                            ->label('Rol')
                            ->formatStateUsing(fn ($state) => $state ? $state->getLabel() : 'Sin rol')
                            ->colors([
                                'danger' => static fn ($state): bool => in_array($state, [UserRole::ADMIN, UserRole::DIRECTOR]),
                                'info' => static fn ($state): bool => in_array($state, [UserRole::MEDICO_GENERAL]),
                                'success' => static fn ($state): bool => in_array($state, [UserRole::NUTRICIONISTA, UserRole::PSICOLOGO]),
                                'gray' => static fn ($state): bool => in_array($state, [UserRole::FARMACIA, UserRole::RECEPCIONISTA]),
                                'primary' => static fn ($state): bool => $state === UserRole::ENFERMERO,
                            ]),
                         /*   
                        IconColumn::make('email_verified_at')
                            ->label('Verificado')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-mark')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->alignCenter(),
                            */
                    ])->alignEnd(),
                    
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                ViewAction::make()->label('Ver'),
                Action::make('change_role')
                    ->label('Cambiar Rol')
                    ->form([
                        \Filament\Forms\Components\Select::make('role')
                            ->label('Nuevo rol')
                            ->options(\App\Enums\UserRole::class)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Contraseña actual')
                            ->password()
                            ->rule('current_password')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update(['role' => $data['role']]);
                    }),
                DeleteAction::make()
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
