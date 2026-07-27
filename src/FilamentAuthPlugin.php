<?php

namespace Housetrevethan\FilamentAuth;

use Housetrevethan\FilamentAuth\Filament\Pages\EditProfile;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

class FilamentAuthPlugin implements Plugin
{
    protected bool $mfaApp = true;

    protected bool $mfaEmail = true;

    protected bool $microsoftOAuth = false;

    protected bool $registerUserResource = true;

    protected bool $registerEditProfile = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getCurrentPanel()->getPlugin(static::class);

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-auth';
    }

    public function register(Panel $panel): void
    {
        if ($this->registerUserResource) {
            $panel->resources([UserResource::class]);
        }

        if ($this->registerEditProfile) {
            $panel->profile(EditProfile::class, isSimple: false);
        }

        $mfaDrivers = [];

        if ($this->mfaApp) {
            $mfaDrivers[] = AppAuthentication::make()
                ->recoverable()
                ->recoveryCodeCount((int) config('filament-auth.mfa.recovery_code_count', 8));
        }

        if ($this->mfaEmail) {
            $mfaDrivers[] = EmailAuthentication::make()
                ->codeExpiryMinutes((int) config('filament-auth.mfa.code_expiry_minutes', 5));
        }

        if (! empty($mfaDrivers)) {
            $panel->multiFactorAuthentication($mfaDrivers);
        }
    }

    public function boot(Panel $panel): void
    {
        if (! $this->microsoftOAuth) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): HtmlString => new HtmlString(
                '<div style="text-align:center; margin-top: 1rem;">
                    <a href="' . route('auth.microsoft.redirect') . '"
                       style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1rem;
                              border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem;
                              text-decoration:none; color:inherit;">
                        Sign in with Microsoft
                    </a>
                </div>'
            ),
            scopes: $panel->getId(),
        );
    }

    public function mfa(bool $app = true, bool $email = true): static
    {
        $this->mfaApp   = $app;
        $this->mfaEmail = $email;

        return $this;
    }

    public function microsoftOAuth(): static
    {
        $this->microsoftOAuth = true;

        return $this;
    }

    public function userResource(bool $register = true): static
    {
        $this->registerUserResource = $register;

        return $this;
    }

    public function editProfile(bool $register = true): static
    {
        $this->registerEditProfile = $register;

        return $this;
    }
}
