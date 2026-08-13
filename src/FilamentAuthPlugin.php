<?php

namespace Housetrevethan\FilamentAuth;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Contracts\Plugin;
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
        $mfaDrivers = [];

        if ($this->mfaApp) {
            $mfaDrivers[] = AppAuthentication::make()
                ->recoverable()
                ->recoveryCodeCount((int)config('filament-auth.mfa.recovery_code_count', 8));
        }

        if ($this->mfaEmail) {
            $mfaDrivers[] = EmailAuthentication::make()
                ->codeExpiryMinutes((int)config('filament-auth.mfa.code_expiry_minutes', 5));
        }

        if (!empty($mfaDrivers)) {
            $panel->multiFactorAuthentication($mfaDrivers);
        }
    }

    public function boot(Panel $panel): void
    {
        if (!$this->microsoftOAuth) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn(): HtmlString => new HtmlString(
                '<div style="text-align:center; margin-top: 1rem;">
                    <a href="' . route('auth.microsoft.redirect') . '"
                       style="display:inline-flex; align-items:center; gap:0; padding:0;
                              border:1px solid #8c8c8c; border-radius:0; font-size:0.875rem;
                              text-decoration:none; color:#5e5e5e; background:#fff;
                              font-family: Segoe UI, sans-serif;">
                        <span style="display:flex; align-items:center; justify-content:center;
                                     width:41px; height:41px; background:#fff; flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                                <rect x="11" y="1" width="9" height="9" fill="#00a4ef"/>
                                <rect x="1" y="11" width="9" height="9" fill="#7fba00"/>
                                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                            </svg>
                        </span>
                        <span style="padding: 0 12px; font-weight:600; font-size:13px; white-space:nowrap;">
                            Sign in with Microsoft
                        </span>
                    </a>
                </div>'
            ),
            scopes: Login::class,
        );
    }

    public function mfa(bool $app = true, bool $email = true): static
    {
        $this->mfaApp = $app;
        $this->mfaEmail = $email;

        return $this;
    }

    public function microsoftOAuth(): static
    {
        $this->microsoftOAuth = true;

        return $this;
    }
}
