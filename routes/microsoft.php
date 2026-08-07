<?php

use Housetrevethan\FilamentAuth\Http\Controllers\MicrosoftLoginController;
use Illuminate\Support\Facades\Route;

Route::get('auth/microsoft/redirect', [MicrosoftLoginController::class, 'create'])
    ->name('auth.microsoft.redirect');

Route::get('auth/microsoft/callback', [MicrosoftLoginController::class, 'store'])
    ->name('auth.microsoft.callback');
