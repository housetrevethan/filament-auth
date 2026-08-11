<?php

namespace Housetrevethan\FilamentAuth\Support;

use Housetrevethan\FilamentAuth\Enums\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resolves role metadata across both sources of roles: the code-defined
 * defaults in config, and roles created at runtime in the database.
 *
 * Package code should ask questions of this class rather than naming roles
 * directly, so applications can add their own without forking anything.
 */
class RoleRegistry
{
    /** @var list<string>|null */
    protected ?array $cachedNames = null;

    public function guardName(): string
    {
        return config('auth.defaults.guard', 'web');
    }

    /**
     * @return class-string
     */
    public function roleClass(): string
    {
        return config('permission.models.role', \Spatie\Permission\Models\Role::class);
    }

    /**
     * Role names known to the application, preferring the database so that
     * runtime-created roles are included.
     *
     * @return list<string>
     */
    public function names(): array
    {
        if ($this->cachedNames !== null) {
            return $this->cachedNames;
        }

        $stored = $this->storedNames();

        return $this->cachedNames = $stored !== [] ? $stored : array_keys($this->definitions());
    }

    /**
     * Forget the memoised role list, for use after roles are created or
     * deleted within the same request.
     */
    public function flush(): void
    {
        $this->cachedNames = null;
    }

    /**
     * @return list<string>
     */
    protected function storedNames(): array
    {
        try {
            if (! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
                return [];
            }

            $roleClass = $this->roleClass();

            return $roleClass::query()
                ->where('guard_name', $this->guardName())
                ->orderBy('name')
                ->pluck('name')
                ->all();
        } catch (Throwable) {
            // The permission tables may not exist yet (fresh install, or
            // migrations still pending). Fall back to the config defaults.
            return [];
        }
    }

    /**
     * @return array<string, array{label?: string, permissions?: array<int, string>|string}>
     */
    public function definitions(): array
    {
        return config('filament-auth.roles.definitions', []);
    }

    public function label(string $role): string
    {
        return $this->definitions()[$role]['label'] ?? Str::headline($role);
    }

    public function isProtected(string $role): bool
    {
        return in_array($role, config('filament-auth.roles.protected', []), true);
    }

    public function isSuper(string $role): bool
    {
        return $role === config('filament-auth.roles.super');
    }

    public function default(): ?string
    {
        return config('filament-auth.roles.default');
    }

    public function exists(string $role): bool
    {
        return in_array($role, $this->names(), true);
    }

    /**
     * Permission names currently granted to a role.
     *
     * @return list<string>
     */
    public function permissionsFor(string $role): array
    {
        try {
            $roleClass = $this->roleClass();

            $model = $roleClass::query()
                ->where('name', $role)
                ->where('guard_name', $this->guardName())
                ->first();

            if ($model === null) {
                return $this->definedPermissionsFor($role);
            }

            return $model->permissions->pluck('name')->all();
        } catch (Throwable) {
            return $this->definedPermissionsFor($role);
        }
    }

    /**
     * @return list<string>
     */
    public function definedPermissionsFor(string $role): array
    {
        $permissions = $this->definitions()[$role]['permissions'] ?? [];

        if ($permissions === '*') {
            return Permission::values();
        }

        return array_values((array) $permissions);
    }

    /**
     * Roles the given actor is permitted to assign, as name => label.
     *
     * @return array<string, string>
     */
    public function assignableBy(mixed $actor, mixed $model = null): array
    {
        if ($actor === null) {
            return [];
        }

        $model ??= config('filament-auth.user_model');

        $assignable = [];

        foreach ($this->names() as $name) {
            if ($actor->can('assignUserRole', [$model, $name])) {
                $assignable[$name] = $this->label($name);
            }
        }

        return $assignable;
    }
}
