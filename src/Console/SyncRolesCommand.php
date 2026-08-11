<?php

namespace Housetrevethan\FilamentAuth\Console;

use Housetrevethan\FilamentAuth\Enums\Permission as PackagePermission;
use Housetrevethan\FilamentAuth\Support\RoleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class SyncRolesCommand extends Command
{
    protected $signature = 'filament-auth:sync-roles
        {--prune : Remove permissions from defined roles that are not listed in config}';

    protected $description = 'Sync the roles and permissions defined in config/filament-auth.php into the database';

    public function handle(RoleRegistry $registry): int
    {
        if (! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
            $this->error('The permission tables are missing. Run "php artisan migrate" first.');

            return self::FAILURE;
        }

        $guard = $registry->guardName();
        $roleClass = $registry->roleClass();
        $permissionClass = config('permission.models.permission', \Spatie\Permission\Models\Permission::class);

        $this->info("Syncing roles and permissions (guard: {$guard})...");
        $this->newLine();

        $permissions = $this->permissionNames($registry);

        foreach ($permissions as $permission) {
            $permissionClass::findOrCreate($permission, $guard);
        }

        $this->line('  Permissions registered: <comment>' . count($permissions) . '</comment>');

        foreach ($registry->definitions() as $name => $definition) {
            $role = $roleClass::findOrCreate($name, $guard);

            $granted = $registry->definedPermissionsFor($name);

            if ($this->option('prune')) {
                $role->syncPermissions($granted);
                $this->line("  Role <comment>{$name}</comment> synced (" . count($granted) . ' permissions)');

                continue;
            }

            // Additive by default, so permissions granted to a default role at
            // runtime are not silently revoked by a deploy.
            $missing = array_values(array_diff($granted, $role->permissions->pluck('name')->all()));

            if ($missing !== []) {
                $role->givePermissionTo($missing);
            }

            $this->line("  Role <comment>{$name}</comment> ok (" . count($missing) . ' permissions added)');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info('Roles and permissions synced.');

        return self::SUCCESS;
    }

    /**
     * Every package permission, plus any additional permission referenced by a
     * role definition, so applications can invent their own keys in config.
     *
     * @return list<string>
     */
    protected function permissionNames(RoleRegistry $registry): array
    {
        $permissions = PackagePermission::values();

        foreach (array_keys($registry->definitions()) as $name) {
            $permissions = array_merge($permissions, $registry->definedPermissionsFor($name));
        }

        return array_values(array_unique($permissions));
    }
}
