<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages;

use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Roles live in a pivot table, so the selected role is held here and
     * applied once the record exists.
     */
    protected ?string $pendingRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $role = $data['role'] ?? null;

        unset($data['role']);

        if (blank($role)) {
            $this->pendingRole = config('filament-auth.roles.default');

            return $data;
        }

        abort_unless(auth()->user()->can('assignUserRole', [static::getModel(), $role]), 403);

        $this->pendingRole = $role;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->pendingRole)) {
            $this->record->syncRoles([$this->pendingRole]);
        }
    }
}
