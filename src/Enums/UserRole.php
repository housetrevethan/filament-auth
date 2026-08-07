<?php

namespace Housetrevethan\FilamentAuth\Enums;

use Filament\Support\Contracts\HasLabel;

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
