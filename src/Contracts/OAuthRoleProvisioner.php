<?php

namespace Housetrevethan\FilamentAuth\Contracts;

use Housetrevethan\FilamentAuth\Models\User;

interface OAuthRoleProvisioner
{
    /**
     * Provision roles, permissions, or initial state for a newly created OAuth user.
     */
    public function provisionRoles(User $oauthUser): void;
}
