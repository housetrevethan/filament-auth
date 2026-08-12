<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
