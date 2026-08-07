<?php

use Housetrevethan\FilamentAuth\Http\Controllers\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;

Route::get('auth/microsoft/redirect', [MicrosoftLoginController::class, 'redirect'])
    ->name('auth.microsoft.redirect');

Route::get('auth/microsoft/callback', [MicrosoftLoginController::class, 'callback'])
    ->name('auth.microsoft.callback');
