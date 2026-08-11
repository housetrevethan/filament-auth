<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages;

use Housetrevethan\FilamentAuth\Enums\UserRole;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! array_key_exists('role', $data)) {
            return $data;
        }

        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        abort_unless(auth()->user()->can('assignUserRole', [static::getModel(), $role]), 403);

        return $data;
    }
}
