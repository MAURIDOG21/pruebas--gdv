<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Filament\Notifications\Notification;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class SecuritySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $title = 'Seguridad';
    protected static ?string $slug = 'security';
    protected string $view = 'filament.pages.security-settings';

    public static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value;
        return ! in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value, UserRole::ENFERMERO->value], true);
    }

    public function mount(): void
    {
        $role = Auth::user()?->role?->value;
        if (in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value, UserRole::ENFERMERO->value], true)) {
            abort(403);
        }
    }

    public ?string $code = null;

    protected function getHeaderActions(): array
    {
        if (in_array(Auth::user()?->role?->value, [\App\Enums\UserRole::MEDICO_GENERAL->value, \App\Enums\UserRole::RECEPCIONISTA->value, \App\Enums\UserRole::ENFERMERO->value], true)) {
            return [];
        }
        return [
            Action::make('enable')
                ->label('Habilitar 2FA')
                ->visible(fn () => ($u = \App\Models\User::find(Auth::id())) && $u->two_factor_secret === null)
                ->action(function () {
                    $user = \App\Models\User::find(Auth::id());
                    $provider = app(TwoFactorAuthenticationProvider::class);
                    $secret = $provider->generateSecretKey();
                    $codes = collect(range(1, 8))->map(fn () => Str::random(10))->all();
                    $user->forceFill([
                        'two_factor_secret' => Crypt::encryptString($secret),
                        'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
                        'two_factor_confirmed_at' => null,
                    ])->save();
                    Notification::make()->title('2FA habilitada, confirma con un código')->success()->send();
                }),
            Action::make('confirm')
                ->label('Confirmar 2FA')
                ->form([
                    TextInput::make('code')->label('Código TOTP')->required(),
                ])
                ->visible(fn () => ($u = \App\Models\User::find(Auth::id())) && $u->two_factor_secret !== null && $u->two_factor_confirmed_at === null)
                ->action(function (array $data) {
                    $user = \App\Models\User::find(Auth::id());
                    $provider = app(TwoFactorAuthenticationProvider::class);
                    $secret = Crypt::decryptString($user->two_factor_secret);
                    if ($provider->verify($secret, $data['code'])) {
                        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
                        Notification::make()->title('2FA confirmada')->success()->send();
                    } else {
                        Notification::make()->title('Código inválido')->danger()->send();
                    }
                }),
            Action::make('regenerate')
                ->label('Regenerar códigos de recuperación')
                ->visible(fn () => ($u = \App\Models\User::find(Auth::id())) && $u->two_factor_secret !== null)
                ->action(function () {
                    $user = \App\Models\User::find(Auth::id());
                    $codes = collect(range(1, 8))->map(fn () => Str::random(10))->all();
                    $user->forceFill([
                        'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
                    ])->save();
                    Notification::make()->title('Códigos regenerados')->success()->send();
                }),
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
                }),
        ];
    }

    protected function getViewData(): array
    {
        $user = \App\Models\User::find(Auth::id());
        if ($user && $user->two_factor_secret && $user->two_factor_confirmed_at === null) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $uri = app(TwoFactorAuthenticationProvider::class)->qrCodeUrl('SIGES-PROGRESO', $user->email, $secret);
            $writer = new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd()));
            $qrSvg = $writer->writeString($uri);
            return [
                'qrSvg' => $qrSvg,
                'secret' => $secret,
            ];
        }
        return [
            'qrSvg' => null,
            'secret' => null,
        ];
    }
}
