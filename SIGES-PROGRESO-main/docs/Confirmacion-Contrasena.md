# Confirmación de Contraseña para Acciones Críticas

## Resumen
**Objetivo:** exigir la contraseña actual del usuario antes de ejecutar acciones críticas (destructivas o de alto impacto) en el sistema, reduciendo riesgos por error humano o intentos maliciosos.  
**Stack:** Filament (Actions y formularios modales) + validación nativa de Laravel (`current_password`).

## Blueprint de Implementación
- Las acciones críticas se protegen con un modal de confirmación que incluye un campo de contraseña.
- Validación con la regla `current_password` (verifica la contraseña del usuario autenticado).
- Solo si la validación pasa, se ejecuta la acción.

```php
\Filament\Actions\DeleteAction::make()
    ->label('Eliminar')
    ->requiresConfirmation()
    ->form([
        \Filament\Forms\Components\TextInput::make('password')
            ->label('Contraseña actual')
            ->password()
            ->rule('current_password')
            ->required(),
    ])
```

## Acciones Protegidas y Cambios Realizados
### Gestión de Personal (UserResource)
**Tabla de usuarios** — archivo `app/Filament/Resources/Users/Tables/UsersTable.php`
- `Eliminar usuario`: protegido con confirmación de contraseña.
- `Cambiar rol`: acción nueva en la tabla que solicita el rol destino y la contraseña actual.

```php
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
```

**Edición de usuario** — archivo `app/Filament/Resources/Users/Pages/EditUser.php`
- `DeleteAction` en el encabezado protegido con contraseña.

```php
DeleteAction::make()
    ->requiresConfirmation()
    ->form([
        \Filament\Forms\Components\TextInput::make('password')
            ->label('Contraseña actual')
            ->password()
            ->rule('current_password')
            ->required(),
    ])
```

### Expedientes (MedicalRecordResource)
**Tabla de expedientes** — archivo `app/Filament/Resources/MedicalRecords/Tables/MedicalRecordsTable.php`
- `Eliminar expediente`: protegido con confirmación de contraseña.

```php
->recordActions([
    ViewAction::make(),
    EditAction::make(),

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
```

**Edición de expediente** — archivo `app/Filament/Resources/MedicalRecords/Pages/EditMedicalRecord.php`
- `DeleteAction` en el encabezado protegido con contraseña.

```php
DeleteAction::make()
    ->requiresConfirmation()
    ->form([
        \Filament\Forms\Components\TextInput::make('password')
            ->label('Contraseña actual')
            ->password()
            ->rule('current_password')
            ->required(),
    ])
```

### Seguridad (2FA)
**Deshabilitar 2FA** — archivo `app/Filament/Pages/SecuritySettings.php`
- La acción `Deshabilitar 2FA` requiere confirmación de contraseña.

```php
Action::make('disable')
    ->label('Deshabilitar 2FA')
    ->color('danger')
    ->visible(fn () => ($u = \App\Models\User::find(Auth::id())) && $u->two_factor_secret !== null)
    ->form([
        TextInput::make('password')
            ->label('Contraseña actual')
            ->password()
            ->rule('current_password')
            ->required(),
    ])
    ->requiresConfirmation()
    ->action(function () {
        $user = \App\Models\User::find(Auth::id());
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        Notification::make()->title('2FA deshabilitada')->success()->send();
    })
```

## UX y Seguridad
- El usuario ve un modal de confirmación con el campo “Contraseña actual”.
- Filament valida con `current_password` (Laravel) antes de ejecutar.
- Minimiza riesgos: evita eliminación/alteración accidental y requiere posesión de credenciales.

## Pruebas de Verificación
1. Intenta eliminar un usuario/expediente: el sistema solicita contraseña; si la contraseña es válida, la acción se ejecuta.
2. Intenta cambiar el rol de un usuario: ingresa rol destino y contraseña; se aplica solo si la contraseña coincide.
3. Deshabilita 2FA desde “Seguridad”: el sistema solicita contraseña antes de borrar secretos.

## Consideraciones
- `current_password` valida contra el usuario autenticado en sesión.
- Si usas proveedores personalizados de autenticación, asegúrate de que el guard coincida (`web`).
- La regla utiliza el hashing y configuración definida por Laravel para contraseñas.

## Referencias de Archivos
- Usuarios:
  - `app/Filament/Resources/Users/Tables/UsersTable.php`
  - `app/Filament/Resources/Users/Pages/EditUser.php`
- Expedientes:
  - `app/Filament/Resources/MedicalRecords/Tables/MedicalRecordsTable.php`
  - `app/Filament/Resources/MedicalRecords/Pages/EditMedicalRecord.php`
- Seguridad 2FA:
  - `app/Filament/Pages/SecuritySettings.php`

## Buenas Prácticas Complementarias
- Registrar auditoría de estas acciones críticas (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).
- Acompañar con 2FA obligatoria para personal con privilegios elevados.
- Añadir notificaciones al correo de cambios de rol o eliminación de registros sensibles.