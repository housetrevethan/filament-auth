<?php

namespace Housetrevethan\FilamentAuth\Contracts;

use Housetrevethan\FilamentAuth\Models\User;
use Illuminate\Http\RedirectResponse;

interface OAuthLoginServiceContract
{
    /**
     * Validate the OAuth user, provision or update the system user,
     * and return a redirect response.
     *
     * Returns null if the OAuth user should be rejected (e.g. tenant mismatch,
     * email already registered as a local account).
     */
    public function handle(): RedirectResponse;
}
