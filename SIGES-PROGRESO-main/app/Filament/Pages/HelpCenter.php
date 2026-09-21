<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class HelpCenter extends Page
{
    // Icono del menú (signo de interrogación)
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    // Apunta al archivo de vista que crearemos abajo
    protected  string $view = 'filament.pages.help-center';

    protected static ?string $navigationLabel = 'Centro de Ayuda';

    protected static ?string $title = 'Centro de Ayuda y Tutoriales';

    protected static ?string $slug = 'ayuda';

    protected static ?int $navigationSort = 100; // Para que aparezca al final

    // Opcional: Si quieres que solo ciertos roles lo vean, descomenta esto
    /*
    public static function canAccess(): bool
    {
        return Auth::user()->can('view_help');
    }
    */
}