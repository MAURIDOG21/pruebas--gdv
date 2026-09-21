<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $navigationLabel = 'Personal de Salud';
    protected static ?string $modelLabel = 'Miembro del Personal';
    protected static ?string $pluralModelLabel = 'Personal de Salud';

    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value;
        return ! in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value, UserRole::ENFERMERO->value], true);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table); 
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getTabs(): array
    {
        return [
            'todos' => Tabs::make('Todos')
                ->label('Todos'),
                
            'doctores' => Tabs::make('Doctores')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [UserRole::MEDICO_GENERAL->value]))
                ->badge(User::query()->whereIn('role', [UserRole::MEDICO_GENERAL->value])->count())
                ->badgeColor('info'),
                
            'administrativos' => Tabs::make('Administrativos')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [UserRole::ADMIN->value, UserRole::DIRECTOR->value, UserRole::RECEPCIONISTA->value]))
                ->badge(User::query()->whereIn('role', [UserRole::ADMIN->value, UserRole::DIRECTOR->value, UserRole::RECEPCIONISTA->value])->count())
                ->badgeColor('danger'),
                
            'especialistas' => Tabs::make('Especialistas')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [UserRole::NUTRICIONISTA->value, UserRole::PSICOLOGO->value]))
                ->badge(User::query()->whereIn('role', [UserRole::NUTRICIONISTA->value, UserRole::PSICOLOGO->value])->count())
                ->badgeColor('success'),
                
            'apoyo' => Tabs::make('Personal de Apoyo')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', [UserRole::FARMACIA->value, UserRole::ENFERMERO->value]))
                ->badge(User::query()->whereIn('role', [UserRole::FARMACIA->value, UserRole::ENFERMERO->value])->count())
                ->badgeColor('gray'),
        ];
    }
        public static function getNavigationSort(): ?int
    {
    return 5;
    }
}
