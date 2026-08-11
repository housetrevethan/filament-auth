<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages;

use Housetrevethan\FilamentAuth\Enums\UserRole;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! array_key_exists('role', $data)) {
            return $data;
        }

        $actor = auth()->user();
        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        abort_unless(
            $actor->can('changeUserRole', $this->record) && $actor->can('assignUserRole', [$this->record::class, $role]),
            403,
        );

        return $data;
    }
}
