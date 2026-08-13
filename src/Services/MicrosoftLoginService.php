<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Concerns\MicrosoftIdentityConcerns;
use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;
use Housetrevethan\FilamentAuth\Enums\OAuthProviderNames;
use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User;

class MicrosoftLoginService
{
    public function __construct(protected OAuthRoleProvisioner $oauthRoleProvisioner)
    {

    }

    public static function validTenantId(User $systemUser): bool
    {
        $tenantId = $systemUser->tenant['id'] ?? null;
        $userEmail = $systemUser->getEmail();

        // Must have a valid tenant id
        if (!$tenantId) return false;

        if (in_array($tenantId, config('filament-auth.microsoft.allowed_tenant_ids'))) {
            Log::info("Tenant ID confirmed for $userEmail.");
            return true;
        }
        return false;
    }

    public function getAndValidateSystemUser(User $socialiteUser): array
    {
        $oauthUserData = [
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
            'oauth-user-id' => $socialiteUser->getId(),
            'oauth-provider-id' => $socialiteUser->tenant['id'] ?? null,
            'oauth-provider-token' => $socialiteUser->token,
            'oauth-provider-name' => OAuthProviderNames::Microsoft->value,
        ];

        // Resolve the account by the provider's immutable identifier, never by
        // the email claim. Email addresses are mutable and can be reassigned by
        // a tenant administrator, so matching on them would let one identity
        // bind itself to another user's account.
        $systemUser = SystemUser::where('oauth_provider_name', $oauthUserData['oauth-provider-name'])
            ->where('oauth_provider_user_id', $oauthUserData['oauth-user-id'])
            ->first();

        if ($systemUser !== null) {
            return MicrosoftIdentityConcerns::updateExistingIdentity($systemUser, $oauthUserData);
        }

        $emailOwner = SystemUser::where('email', $oauthUserData['email'])->first();

        if ($emailOwner === null) {
            return [
                'system-user' => MicrosoftIdentityConcerns::createUserFromIdentity($oauthUserData),
                'rejection-reason' => null
            ];
        }

        // The email is already taken by a different OAuth identity. Rebinding
        // it would hand this login control of that account, so refuse.
        if ($emailOwner->oauth_provider_user_id !== null) {
            Log::warning(
                "Rejected Microsoft login: {$oauthUserData['email']} is already bound to a different OAuth identity."
            );

            return ['system-user' => null, 'rejection-reason' => OAuthRejectionReason::EmailConflict];
        }

        // The user has a local account. We return null here and reject the
        // login to avoid a database error.
        Log::info("User already has a local account: {$oauthUserData['email']}");

        return ['system-user' => null, 'rejection-reason' => OAuthRejectionReason::LocalAccount];
    }
}
