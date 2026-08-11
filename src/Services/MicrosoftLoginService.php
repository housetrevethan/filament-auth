<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
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

    public ?OAuthRejectionReason $rejectionReason = null;

    protected const PROVIDER_NAME = 'microsoft';

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
        // Resolve the account by the provider's immutable identifier, never by
        // the email claim. Email addresses are mutable and can be reassigned by
        // a tenant administrator, so matching on them would let one identity
        // bind itself to another user's account.
        $systemUser = SystemUser::where('oauth_provider_name', self::PROVIDER_NAME)
            ->where('oauth_provider_user_id', $this->microsoftUserId)
            ->first();

        if ($systemUser !== null) {
            return $this->updateExistingIdentity($systemUser);
        }

        $emailOwner = SystemUser::where('email', $this->microsoftUserEmail)->first();

        if ($emailOwner === null) {
            return $this->createSystemUser();
        }

        // The email is already taken by a different OAuth identity. Rebinding
        // it would hand this login control of that account, so refuse.
        if ($emailOwner->oauth_provider_user_id !== null) {
            Log::warning(
                "Rejected Microsoft login: $this->microsoftUserEmail is already bound to a different OAuth identity."
            );
            $this->rejectionReason = OAuthRejectionReason::EmailConflict;

            return null;
        }

        // The user has a local account. We return null here and reject the
        // login to avoid a database error.
        Log::info("User already has a local account: $this->microsoftUserEmail");
        $this->rejectionReason = OAuthRejectionReason::LocalAccount;

        return null;
    }

    protected function createSystemUser(): SystemUser
    {
        Log::info("User does not exist. Creating the user with email: $this->microsoftUserEmail");

        return SystemUser::create([
            'name' => $this->microsoftUserName,
            'email' => $this->microsoftUserEmail,
            'oauth_provider_id' => $this->microsoftTenantId,
            'oauth_provider_name' => self::PROVIDER_NAME,
            'oauth_provider_user_id' => $this->microsoftUserId,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
            'role' => $this->getUserRole(),
            'avatar' => $this->microsoftAvatarUrl,
        ]);
    }

    protected function updateExistingIdentity(SystemUser $systemUser): ?SystemUser
    {
        // The identity is known but is arriving from a different tenant than
        // the one it was provisioned under. Moving an account between tenants
        // changes its role and trust level, so it must be done deliberately.
        if ($systemUser->oauth_provider_id !== $this->microsoftTenantId) {
            Log::warning(
                "Rejected Microsoft login for $this->microsoftUserEmail: tenant changed from "
                . "$systemUser->oauth_provider_id to $this->microsoftTenantId."
            );
            $this->rejectionReason = OAuthRejectionReason::TenantMismatch;

            return null;
        }

        Log::info("User has a previous login, updating profile for $this->microsoftUserEmail");

        $attributes = [
            'name' => $this->microsoftUserName,
            'role' => $this->getUserRole(),
            'avatar' => $this->microsoftAvatarUrl,
        ];

        // Sync the email only when it is not already held by another account,
        // otherwise the unique constraint would abort the login.
        if ($systemUser->email !== $this->microsoftUserEmail) {
            $emailTaken = SystemUser::where('email', $this->microsoftUserEmail)
                ->whereKeyNot($systemUser->getKey())
                ->exists();

            if ($emailTaken) {
                Log::warning(
                    "Could not sync email for identity $this->microsoftUserId: "
                    . "$this->microsoftUserEmail is already in use by another account."
                );
            } else {
                $attributes['email'] = $this->microsoftUserEmail;
            }
        }

        $systemUser->update($attributes);

        return $systemUser;
    }
}
