<?php

namespace Housetrevethan\FilamentAuth\DataObjects;

use Housetrevethan\FilamentAuth\Enums\OAuthRejectionReason;
use Housetrevethan\FilamentAuth\Models\User as SystemUser;

/**
 * The outcome of resolving an OAuth identity to a local account.
 */
readonly class OAuthLoginResult
{
    private function __construct(
        public ?SystemUser $user,
        public ?OAuthRejectionReason $rejectionReason,
        public bool $isNewUser = false,
    ) {
    }

    public static function created(SystemUser $user): self
    {
        return new self($user, null, true);
    }

    public static function existing(SystemUser $user): self
    {
        return new self($user, null, false);
    }

    public static function rejected(OAuthRejectionReason $reason): self
    {
        return new self(null, $reason, false);
    }

    public function wasSuccessful(): bool
    {
        return $this->user !== null;
    }
}
