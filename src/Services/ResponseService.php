<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;

class ResponseService
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
    }}