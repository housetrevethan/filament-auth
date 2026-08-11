<?php

namespace Housetrevethan\FilamentAuth\Policies;

use Housetrevethan\FilamentAuth\Enums\UserRole;
use Housetrevethan\FilamentAuth\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function editProfile(User $user, User $model): bool
    {
        return $user->role === UserRole::HouseTrevethanStaff || $user->role === UserRole::Admin;
    }

    public function changeUserRole(User $user, ?User $model = null): bool
    {
        if ($user->role === UserRole::HouseTrevethanStaff) {
            return true;
        }

        if ($user->role !== UserRole::Admin) {
            return false;
        }

        // Admins may manage roles, but never those of House Trevethan staff.
        return $model === null || $model->role !== UserRole::HouseTrevethanStaff;
    }

    public function assignUserRole(User $user, UserRole $role): bool
    {
        if ($user->role === UserRole::HouseTrevethanStaff) {
            return true;
        }

        return $user->role === UserRole::Admin && $role !== UserRole::HouseTrevethanStaff;
    }
}
