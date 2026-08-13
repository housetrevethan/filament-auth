<?php

namespace Housetrevethan\FilamentAuth\Http\Controllers;

use Filament\Notifications\Notification;
use Housetrevethan\FilamentAuth\Enums\OAuthProviderNames;
use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Services\MicrosoftLoginService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftLoginController extends Controller
{
    public function __construct(protected MicrosoftLoginService $microsoftLoginService)
    {
    }

    /*
     *
     */
    public function create()
    {
        return Socialite::driver(OAuthProviderNames::Microsoft->value)->redirect();
    }

    public function store()
    {
        $socialiteUser = Socialite::driver(OAuthProviderNames::Microsoft->value)->user();

        // The tenant has to clear before the identity is worth resolving.
        $tenantRejection = $this->microsoftLoginService->resolveTenantRejection($socialiteUser);

        if ($tenantRejection !== null) {
            return $this->rejectLogin($tenantRejection);
        }

        $userValidation = $this->microsoftLoginService->getAndValidateSystemUser($socialiteUser);
        $systemUser = $userValidation['system-user'];

        if ($systemUser === null) {
            return $this->rejectLogin($userValidation['rejection-reason']);
        }

        Auth::login($systemUser);
        request()->session()->regenerateToken();
        Notification::make()
            ->title('Welcome from the House Trevethan Team!')
            ->success()
            ->body('Enjoy your new system and feel free to reach out if you have any questions!')
            ->send();

        return redirect(route(config('filament-auth.filament-routes.filament-dashboard-route')));
    }

    /**
     * Tear down any half-established session and send the user back with an
     * explanation. Tenant failures go to the dedicated failure route, every
     * other refusal returns to the login screen.
     */
    private function rejectLogin(?OAuthRejectionReason $reason)
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->rejectionNotification($reason)->send();

        $route = match ($reason) {
            OAuthRejectionReason::MissingTenant,
            OAuthRejectionReason::TenantNotAllowed => config('filament-auth.filament-routes.failed-login-redirect'),
            default => config('filament-auth.filament-routes.filament-login-route'),
        };

        return redirect(route($route));
    }

    private function rejectionNotification(?OAuthRejectionReason $reason): Notification
    {
        return match ($reason) {
            OAuthRejectionReason::LocalAccount => Notification::make()
                ->title('Welcome Back! It looks like you already have an account.')
                ->warning()
                ->body('This email is already registered in the system. Please login with your password.'),
            OAuthRejectionReason::EmailConflict,
            OAuthRejectionReason::TenantMismatch,
            OAuthRejectionReason::MissingTenant,
            OAuthRejectionReason::TenantNotAllowed => Notification::make()
                ->title('We could not sign you in.')
                ->danger()
                ->body('Your account could not be verified. Please contact your administrator for assistance.'),
            default => Notification::make()
                ->title('We could not sign you in.')
                ->danger()
                ->body('Please try again or contact your administrator for assistance.'),
        };
    }
}
