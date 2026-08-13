<?php

namespace Housetrevethan\FilamentAuth\DataObjects;

use Housetrevethan\FilamentAuth\Enums\OAuthProviderNames;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * The identity claims presented by the OAuth provider for a single login.
 *
 * This is the payload handed to the consuming application's
 * {@see \Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner} so it can
 * make its own authorization decisions. The package never interprets these
 * values beyond authenticating the user.
 */
readonly class OAuthIdentityContext
{
    public function __construct(
        public OAuthProviderNames $providerName,
        public ?string $providerTenantId,
        public string $providerUserId,
        public ?string $email,
        public ?string $name,
        public ?string $avatar,
        public bool $isNewUser = false,
    ) {
    }

    public static function fromSocialiteUser(
        SocialiteUser $socialiteUser,
        OAuthProviderNames $providerName,
    ): self {
        return new self(
            providerName: $providerName,
            providerTenantId: $socialiteUser->tenant['id'] ?? null,
            providerUserId: (string) $socialiteUser->getId(),
            email: $socialiteUser->getEmail(),
            name: $socialiteUser->getName(),
            avatar: $socialiteUser->getAvatar(),
        );
    }

    public function asNewUser(bool $isNewUser = true): self
    {
        return new self(
            providerName: $this->providerName,
            providerTenantId: $this->providerTenantId,
            providerUserId: $this->providerUserId,
            email: $this->email,
            name: $this->name,
            avatar: $this->avatar,
            isNewUser: $isNewUser,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toUserAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'oauth_provider_id' => $this->providerTenantId,
            'oauth_provider_name' => $this->providerName->value,
            'oauth_provider_user_id' => $this->providerUserId,
        ];
    }
}
