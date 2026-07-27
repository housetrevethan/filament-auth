<?php

namespace Housetrevethan\FilamentAuth\Http\Controllers;

use Housetrevethan\FilamentAuth\Services\MicrosoftLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function callback(): RedirectResponse
    {
        return (new MicrosoftLoginService(Socialite::driver('microsoft')->user()))->handle();
    }
}
