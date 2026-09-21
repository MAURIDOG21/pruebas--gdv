<?php

namespace App\Filament\Resources\MedicalRecords\Pages;

use App\Filament\Resources\MedicalRecords\MedicalRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMedicalRecord extends EditRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\TextInput::make('password')
                        ->label('Contraseña actual')
                        ->password()
                        ->rule('current_password')
                        ->required(),
                ]),
        ];
    }
}
