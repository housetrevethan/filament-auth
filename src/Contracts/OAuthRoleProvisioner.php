<?php
declare(strict_types = 1);

namespace Housetrevethan\FilamentAuth\Contracts;

interface OAuthRoleProvisioner
{
    /**
     * Provision roles, permissions, or initial state for a newly created OAuth user.
     *
     * The array contains the identity data from the OAuth provider:
     *
     * @param array{
     *     email: string,
     *     name: string,
     *     avatar: ?string,
     *     oauth-user-id: string,
     *     oauth-provider-id: ?string,
     *     oauth-provider-token: string,
     *     oauth-provider-name: string,
     * } $oauthUserData
     */
    public function provisionRoles(array $oauthUserData): void;
}
