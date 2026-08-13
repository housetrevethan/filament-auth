<?php

namespace Housetrevethan\FilamentAuth\Concerns;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MicrosoftIdentityConcerns
{

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
        // The identity is known but is arriving from a different tenant than
        // the one it was provisioned under. Moving an account between tenants
        // changes its role and trust level, so it must be done deliberately.
        if ($systemUser->oauth_provider_id !== $oauthUserData['oauth-provider-id']) {
            Log::warning(
                "Rejected Microsoft login for {$oauthUserData['email']}: tenant changed from "
                . "$systemUser->oauth_provider_id to {$oauthUserData['oauth-provider-id']}."
            );

            return [
                'system-user' => null,
                'rejection-reason' => OAuthRejectionReason::TenantMismatch];
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

        return ['system-user' => $systemUser, 'rejection-reason' => null];
    }
}
