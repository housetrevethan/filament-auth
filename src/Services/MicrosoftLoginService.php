<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Laravel\Socialite\Contracts\User;

class MicrosoftLoginService
{
    public string $microsoftUserEmail;

    public string $microsoftUserName;

    public ?string $microsoftAvatarUrl;

    public ?string $microsoftTenantId;

    public string $microsoftUserId;

    public ?string $microsoftUserToken;

    public function __construct(public User $microsoftUser)
    {
        $this->microsoftUserEmail = $this->microsoftUser->getEmail();
        $this->microsoftUserName = $this->microsoftUser->getName();
        $this->microsoftAvatarUrl = $this->microsoftUser->getAvatar();
        $this->microsoftUserId = $this->microsoftUser->getId();
        $this->microsoftUserToken = $this->microsoftUser->token;
        $this->microsoftTenantId = $this->microsoftUser->tenant['id'] ?? null;
    }

    public function validTenantId(): bool
    {
        if (in_array($this->microsoftTenantId, config('filament-auth.microsoft.allowed_tenant_ids')))
        {
            Log::info("Tenant ID confirmed for $this->microsoftUserEmail.");
            return true;
        }
        return false;
    }

    public function getUserRole(): UserRole
    {
        $debugHtStaff = UserRole::HouseTrevethanStaff->getLabel();
        $debugAdmin = UserRole::Admin->getLabel();
        $debugClient = UserRole::Client->getLabel();

        if ($this->microsoftTenantId === config('filament-auth.microsoft.house_trevethan_tenant_id'))
        {
            Log::info("$this->microsoftUserEmail is assigned the following user role: $debugHtStaff");
            return UserRole::HouseTrevethanStaff;
        }
        elseif (in_array($this->microsoftTenantId, config('filament-auth.microsoft.allowed_tenant_ids')))
        {
            Log::info("$this->microsoftUserEmail is assigned the following user role: $debugAdmin");
            return UserRole::Admin;
        }
        
        Log::info("$this->microsoftUserEmail is assigned the following user role: $debugClient");
        return UserRole::Client;
    }

    public function getSystemUser(): ?SystemUser
    {
        $systemUser = SystemUser::where('email', $this->microsoftUserEmail)->first();
        // Brand new user
        if ($systemUser === null) {
            Log::info("User does not exist. Creating the user with email: $this->microsoftUserEmail");

            return SystemUser::create([
                'name' => $this->microsoftUserName,
                'email' => $this->microsoftUserEmail,
                'oauth_provider_id' => $this->microsoftTenantId,
                'oauth_provider_name' => 'microsoft',
                'oauth_provider_user_id' => $this->microsoftUserId,
                'email_verified_at' => now(),
                'remember_token' => hash('sha256', $this->microsoftUserToken),
                'password' => Hash::make(Str::random(40)),
                'role' => $this->getUserRole(),
                'avatar' => $this->microsoftAvatarUrl,
            ]);
        }

        // The user has an OAuth provider from a previous login
        if ($systemUser->oauth_provider_user_id !== null) {
            Log::info("User has a previous login, updating profile for $this->microsoftUserEmail");
            $systemUser->update([
                'name' => $this->microsoftUserName,
                'remember_token' => hash('sha256', $this->microsoftUserToken),
                'oauth_provider_id' => $this->microsoftTenantId,
                'oauth_provider_user_id' => $this->microsoftUserId,
                'role' => $this->getUserRole(),
                'avatar' => $this->microsoftAvatarUrl,
                'password' => Hash::make(Str::random(40)),
            ]);

            return $systemUser;
        }

        // If the user doesn't have an OAuth provider, they have
        // a system account. We return null here and reject the login
        // to avoid a database error
        Log::info("User already has a local account: $this->microsoftUserEmail");

        return null;
    }
}
