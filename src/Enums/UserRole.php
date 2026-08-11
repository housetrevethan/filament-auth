<?php

namespace Housetrevethan\FilamentAuth\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The role names this package ships by default.
 *
 * Roles themselves live in the database (spatie/laravel-permission) so they can
 * be created and edited at runtime. This enum is a convenience for referring to
 * the built-in roles from code; it is not exhaustive.
 */
enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case HouseTrevethanStaff = "house_trevethan_staff";
    case Client = "client";

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin  => 'Admin',
            self::HouseTrevethanStaff => 'House Trevethan Staff',
            self::Client => 'Client'
        };
    }
}
