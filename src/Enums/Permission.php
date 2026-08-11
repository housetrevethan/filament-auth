<?php

namespace Housetrevethan\FilamentAuth\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Permissions shipped by this package.
 *
 * These are the only authorization keys package code ever checks. Roles are
 * bundles of these keys, so consuming applications can introduce their own
 * roles — in config or at runtime — and grant them package behavior without
 * modifying the package.
 */
enum Permission: string implements HasLabel
{
    case AccessPanel = 'panel.access';

    case ViewAnyUser = 'users.viewAny';
    case ViewUser = 'users.view';
    case CreateUser = 'users.create';
    case UpdateUser = 'users.update';
    case DeleteUser = 'users.delete';
    case RestoreUser = 'users.restore';
    case ForceDeleteUser = 'users.forceDelete';
    case EditUserProfile = 'users.editProfile';
    case ChangeUserRole = 'users.changeRole';

    case ManageRoles = 'roles.manage';
    case AssignProtectedRole = 'roles.assignProtected';

    public function getLabel(): string
    {
        return match ($this) {
            self::AccessPanel => 'Access Admin Panel',
            self::ViewAnyUser => 'List Users',
            self::ViewUser => 'View User',
            self::CreateUser => 'Create User',
            self::UpdateUser => 'Update User',
            self::DeleteUser => 'Delete User',
            self::RestoreUser => 'Restore User',
            self::ForceDeleteUser => 'Permanently Delete User',
            self::EditUserProfile => 'Edit User Profile',
            self::ChangeUserRole => 'Change User Role',
            self::ManageRoles => 'Manage Roles',
            self::AssignProtectedRole => 'Assign Protected Roles',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
