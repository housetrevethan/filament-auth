<?php

namespace Housetrevethan\FilamentAuth\Actions;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Housetrevethan\FilamentAuth\Services\ResponseService;
use Illuminate\Support\Facades\Log;

class UpdateExistingOAuthUser
{
    public static function execute(SystemUser $systemUser, array $oauthUserData): array
    {
        // Tenant ID changed since last login, reject user
        if ($systemUser->oauth_provider_id !== $oauthUserData['oauth-provider-id']) {
            Log::warning(
                "Rejected Microsoft login for {$oauthUserData['email']}: tenant changed from "
                . "$systemUser->oauth_provider_id to {$oauthUserData['oauth-provider-id']}."
            );

            return ResponseService::reject(OAuthRejectionReason::TenantMismatch);
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

        return ResponseService::accept($systemUser);
    }
}