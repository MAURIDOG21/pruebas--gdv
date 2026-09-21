<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Filament\Notifications\Notification;

class TwoFactorChallenge extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Verificación 2FA';
    protected static ?string $slug = 'two-factor';
    protected static string|\BackedEnum|null $navigationIcon =  null;
    protected string $view = 'filament.pages.two-factor-challenge';

    public ?string $code = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verificar')
                ->form([
                    TextInput::make('code')->label('Código TOTP o de recuperación')->required(),
                ])
                ->action(function (array $data) {
                    $user = \App\Models\User::find(Auth::id());
                    if (!$user || !$user->two_factor_secret) {
                        Notification::make()->title('2FA no habilitada')->danger()->send();
                        return;
                    }
                    $provider = app(TwoFactorAuthenticationProvider::class);
                    $secret = Crypt::decryptString($user->two_factor_secret);

                    $code = trim($data['code']);
                    $ok = $provider->verify($secret, $code);

                    if (!$ok && $user->two_factor_recovery_codes) {
                        $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?: [];
                        if (in_array($code, $codes, true)) {
                            $ok = true;
                            // consumir el código usado
                            $codes = array_values(array_diff($codes, [$code]));
                            $user->forceFill([
                                'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
                            ])->save();
                        }
                    }

                    if ($ok) {
                        session(['two_factor_passed' => true]);
                        Notification::make()->title('Verificado')->success()->send();
                        redirect('/dashboard');
                        return;
                    }
                    Notification::make()->title('Código inválido')->danger()->send();
                }),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $role = Auth::user()?->role?->value;
        if ($role === UserRole::MEDICO_GENERAL->value) {
            // Do not show any actions or navigation; doctors shouldn't see this link
        }
    }
}
