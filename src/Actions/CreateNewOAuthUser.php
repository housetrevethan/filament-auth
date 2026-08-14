<?php
declare(strict_types = 1);

namespace Housetrevethan\FilamentAuth\Actions;

use Housetrevethan\FilamentAuth\Models\User as SystemUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateNewOAuthUser
{
    public static function execute(array $userData): SystemUser
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
}