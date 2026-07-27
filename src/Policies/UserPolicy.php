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
        return $user->role === UserRole::Core || $user->role === UserRole::Admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->role === UserRole::Core || $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function editProfile(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function changeUserRole(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }
}
