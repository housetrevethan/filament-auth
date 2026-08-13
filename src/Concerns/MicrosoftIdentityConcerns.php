<?php

namespace Housetrevethan\FilamentAuth\Concerns;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MicrosoftIdentityConcerns
{
    /**
     * Build the result for a login that resolved to a usable account.
     *
     * @return array{system-user: SystemUser, rejection-reason: null}
     */
    public static function accept(SystemUser $systemUser): array
    {
        return ['system-user' => $systemUser, 'rejection-reason' => null];
    }

    /**
     * Build the result for a login that was refused.
     *
     * @return array{system-user: null, rejection-reason: OAuthRejectionReason}
     */
    public static function reject(OAuthRejectionReason $reason): array
    {
        return ['system-user' => null, 'rejection-reason' => $reason];
    }

    public static function createUserFromIdentity(array $userData): SystemUser
    {
        Log::info("User does not exist. Creating the user with email: {$userData['email']}");

        return SystemUser::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'oauth_provider_id' => $userData['oauth-provider-id'],
            'oauth_provider_name' => $userData['oauth-provider-name'],
            'oauth_provider_user_id' => $userData['oauth-user-id'],
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
            'avatar' => $userData['avatar'],
        ]);
    }

    public static function updateExistingIdentity(SystemUser $systemUser, array $oauthUserData): array
    {
        // Tenant ID changed since last login, reject user
        if ($systemUser->oauth_provider_id !== $oauthUserData['oauth-provider-id']) {
            Log::warning(
                "Rejected Microsoft login for {$oauthUserData['email']}: tenant changed from "
                . "$systemUser->oauth_provider_id to {$oauthUserData['oauth-provider-id']}."
            );

            return self::reject(OAuthRejectionReason::TenantMismatch);
        }

        Log::info("User has a previous login, updating profile for {$oauthUserData['email']}");

        $attributes = [
            'name' => $oauthUserData['name'],
            'avatar' => $oauthUserData['avatar'],
        ];

        // Sync the email only when it is not already held by another account,
        // otherwise the unique constraint would abort the login.
        if ($systemUser->email !== $oauthUserData['email']) {
            $emailTaken = SystemUser::where('email', $oauthUserData['email'])
                ->whereKeyNot($systemUser->getKey())
                ->exists();

            if ($emailTaken) {
                Log::warning(
                    "Could not sync email for identity {$oauthUserData['oauth-user-id']}: "
                    . "{$oauthUserData['email']} is already in use by another account."
                );
            } else {
                $attributes['email'] = $oauthUserData['email'];
            }
        }

        $systemUser->update($attributes);

        return self::accept($systemUser);
    }
}
