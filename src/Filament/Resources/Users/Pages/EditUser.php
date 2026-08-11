<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages;

use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $pendingRole = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->primaryRoleName();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! array_key_exists('role', $data)) {
            return $data;
        }

        $role = $data['role'];

        unset($data['role']);

        if (blank($role)) {
            return $data;
        }

        $actor = auth()->user();

        abort_unless(
            $actor->can('changeUserRole', $this->record) && $actor->can('assignUserRole', [$this->record::class, $role]),
            403,
        );

        $this->pendingRole = $role;

        return $data;
    }

    protected function afterSave(): void
    {
        if (filled($this->pendingRole)) {
            $this->record->syncRoles([$this->pendingRole]);
        }
    }
}
