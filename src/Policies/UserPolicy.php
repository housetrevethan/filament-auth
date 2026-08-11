<?php

namespace Housetrevethan\FilamentAuth\Policies;

use BackedEnum;
use Housetrevethan\FilamentAuth\Enums\Permission;
use Housetrevethan\FilamentAuth\Models\User;
use Housetrevethan\FilamentAuth\Support\RoleRegistry;

/**
 * Authorization is expressed entirely in permissions, never in role names.
 *
 * This is what lets applications introduce their own roles — in config or at
 * runtime — and have those roles work with the package's user management
 * without overriding this policy.
 */
class UserPolicy
{
    public function __construct(protected RoleRegistry $roles) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewAnyUser->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::ViewUser->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateUser->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UpdateUser->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(Permission::DeleteUser->value);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(Permission::DeleteUser->value);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can(Permission::RestoreUser->value);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can(Permission::ForceDeleteUser->value);
    }

    public function editProfile(User $user, User $model): bool
    {
        return $user->can(Permission::EditUserProfile->value);
    }

    /**
     * Whether the actor may change the role of the given user at all.
     */
    public function changeUserRole(User $user, ?User $model = null): bool
    {
        if (! $user->can(Permission::ChangeUserRole->value)) {
            return false;
        }

        // Demoting someone who holds a protected role is itself a protected
        // action, otherwise a lesser administrator could strip staff access.
        if ($model !== null && $this->holdsProtectedRole($model)) {
            return $user->can(Permission::AssignProtectedRole->value);
        }

        return true;
    }

    /**
     * Whether the actor may grant the given role to a user.
     */
    public function assignUserRole(User $user, BackedEnum|string $role): bool
    {
        $role = $role instanceof BackedEnum ? (string) $role->value : $role;

        if (! $user->can(Permission::ChangeUserRole->value)) {
            return false;
        }

        if (! $this->roles->exists($role)) {
            return false;
        }

        if ($this->roles->isProtected($role) || $this->roles->isSuper($role)) {
            return $user->can(Permission::AssignProtectedRole->value);
        }

        // Prevent privilege escalation: an actor may never grant a role that
        // carries a permission the actor does not already hold. Without this,
        // anyone able to create roles at runtime could promote themselves.
        foreach ($this->roles->permissionsFor($role) as $permission) {
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    protected function holdsProtectedRole(User $model): bool
    {
        foreach ($model->getRoleNames() as $name) {
            if ($this->roles->isProtected($name) || $this->roles->isSuper($name)) {
                return true;
            }
        }

        return false;
    }
}
