<?php

namespace Housetrevethan\FilamentAuth\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Core = 'core';
    case Client = 'client';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin  => 'Admin',
            self::Core   => 'Core',
            self::Client => 'Client',
        };
    }
}
