<?php
declare(strict_types = 1);

namespace Housetrevethan\FilamentAuth\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements
    FilamentUser,
    HasAppAuthentication,
    HasAppAuthenticationRecovery,
    HasAvatar,
    HasEmailAuthentication,
    MustVerifyEmail
{
    use Notifiable;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'has_email_authentication',
        'email_verified_at',
        'remember_token',
        'oauth_provider_id',
        'oauth_provider_name',
        'oauth_provider_user_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
    ];

    /**
     * @return array{email_verified_at: string, password: string, app_authentication_secret: string, app_authentication_recovery_codes: string, has_email_authentication: string}
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'has_email_authentication' => 'boolean',
        ];
    }

    /**
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('access panel');
    }

    /**
     * @return string|null
     */
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    /**
     * @param string|null $secret
     * @return void
     */
    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    /**
     * @return string
     */
    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return array|string[]|null
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param array|null $codes
     * @return void
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    /**
     * @return string|null
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        if (str_starts_with($this->avatar, 'data:')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    /**
     * @return bool
     */
    public function hasOAuthProvider(): bool
    {
        return $this->oauth_provider_id !== null && $this->oauth_provider_name !== null;
    }
}
