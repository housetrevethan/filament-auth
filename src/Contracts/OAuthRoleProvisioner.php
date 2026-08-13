<?php

namespace Housetrevethan\FilamentAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface OAuthRoleProvisioner
{
    /**
     * Provision roles, permissions, or initial state for a newly created OAuth user.
     */
    public function provisionRoles(Authenticatable $oauthUser): void;
}
