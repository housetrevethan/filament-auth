<?php

namespace Housetrevethan\FilamentAuth\Exceptions;

use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;
use RuntimeException;

class OAuthRoleProvisionerNotBound extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'No implementation of [' . OAuthRoleProvisioner::class . '] is bound in the container. '
            . 'Bind OAuthRoleProvisioner in a service provider, e.g.: '
            . '$this->app->bind(\\' . OAuthRoleProvisioner::class . '::class, YourRoleProvisioner::class);'
        );
    }
}
