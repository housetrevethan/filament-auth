<?php

namespace Housetrevethan\FilamentAuth\Http\Controllers;

use Housetrevethan\FilamentAuth\Services\MicrosoftLoginService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller;

class MicrosoftLoginController extends Controller
{
    private string $provider = 'microsoft';

    /*
     *
     */
    public function create()
    {
        return Socialite::driver($this->provider)->redirect();
    }

    public function store()
    {
        $microsoftLoginService = new MicrosoftLoginService(
            Socialite::driver($this->provider)->user()
        );
        // If the user has no tenant ID or the tenant ID is not allowed, deny login
        if (! $microsoftLoginService->validTenantId()) {
            return redirect(route('about'));
        } else {
            $systemUser = $microsoftLoginService->getSystemUser();
            if ($systemUser !== null) {
                Auth::login($systemUser);
                Notification::make()
                    ->title('Welcome from the House Trevethan Team!')
                    ->success()
                    ->body('Enjoy your new system and feel free to reach out if you have any questions!')
                    ->send();

                return redirect(route('filament.admin.pages.dashboard'));
            }

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            Notification::make()
                ->title('Welcome Back! It looks like you already have an account.')
                ->warning()
                ->body('This email is already registered in the system. Please login with your password.')
                ->send();

            return redirect(route('filament.admin.auth.login'));
        }

    }
}

