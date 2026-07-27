<?php

use Housetrevethan\FilamentAuth\Http\Controllers\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;

Route::get('auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])
    ->name('auth.microsoft.redirect');

Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('auth.microsoft.callback');
