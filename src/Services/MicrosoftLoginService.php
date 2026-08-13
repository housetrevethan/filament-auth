<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Concerns\MicrosoftIdentityConcerns;
use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;
use Housetrevethan\FilamentAuth\Enums\OAuthProviderNames;
use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User;
use Throwable;

class MicrosoftLoginService
{
    public function __construct(protected OAuthRoleProvisioner $oauthRoleProvisioner)
    {

    }

    /**
     * Resolve the tenant claim to a rejection reason, or null when the tenant
     * is permitted to authenticate.
     */
    public static function resolveTenant(User $socialiteUser): ?OAuthRejectionReason
    {
        $tenantId = $socialiteUser->tenant['id'] ?? null;
        $userEmail = $socialiteUser->getEmail();

        // Without a tenant id the identity cannot be attributed to an
        // organization, so there is nothing to check it against.
        if (!$tenantId) {
            Log::warning("Rejected Microsoft login for $userEmail: the token carried no tenant id.");

            return OAuthRejectionReason::MissingTenant;
        }

        if (!in_array($tenantId, config('filament-auth.microsoft.allowed_tenant_ids'))) {
            Log::warning("Rejected Microsoft login for $userEmail: tenant $tenantId is not allowed.");

            return OAuthRejectionReason::TenantNotAllowed;
        }

        Log::info("Tenant ID confirmed for $userEmail.");

        return null;
    }

    /**
     * @throws Throwable
     */
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

        // Resolving the identity reads the account and then writes to it, so
        // the whole decision runs in one transaction. Without it a failure
        // partway through could leave a half-provisioned account behind, and
        // two concurrent callbacks for the same new identity could both pass
        // the "does this user exist" check.
        return DB::transaction(fn (): array => $this->resolveSystemUser($oauthUserData));
    }

    /**
     * @return array{system-user: ?SystemUser, rejection-reason: ?OAuthRejectionReason}
     */
    private function resolveSystemUser(array $oauthUserData): array
    {
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

        // Create a new user with the current identity being passed from Entra ID
        if ($emailOwner === null) {
            return MicrosoftIdentityConcerns::accept(
                MicrosoftIdentityConcerns::createUserFromIdentity($oauthUserData)
            );
        }

        // The email is already taken by a different OAuth identity. Rebinding
        // it would hand this login control of that account, so refuse.
        if ($emailOwner->oauth_provider_user_id !== null) {
            Log::warning(
                "Rejected Microsoft login: {$oauthUserData['email']} is already bound to a different OAuth identity."
            );

            return MicrosoftIdentityConcerns::reject(OAuthRejectionReason::EmailConflict);
        }

        // The user has a local account. We return null here and reject the
        // login to avoid a database error.
        Log::info("User already has a local account: {$oauthUserData['email']}");

        return MicrosoftIdentityConcerns::reject(OAuthRejectionReason::LocalAccount);
    }
}
