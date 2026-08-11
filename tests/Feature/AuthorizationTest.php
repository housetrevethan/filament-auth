<?php

namespace Housetrevethan\FilamentAuth\Tests\Feature;

use Housetrevethan\FilamentAuth\Enums\Permission;
use Housetrevethan\FilamentAuth\Models\User;
use Housetrevethan\FilamentAuth\Support\RoleRegistry;
use Housetrevethan\FilamentAuth\Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('filament-auth:sync-roles')->assertSuccessful();
    }

    protected function user(string $role): User
    {
        $user = User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_sync_creates_roles_and_permissions(): void
    {
        $this->assertSame(
            ['admin', 'client', 'house_trevethan_staff'],
            Role::query()->orderBy('name')->pluck('name')->all()
        );

        foreach (Permission::values() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }
    }

    public function test_super_role_bypasses_every_check(): void
    {
        $staff = $this->user('house_trevethan_staff');

        $this->assertTrue($staff->can(Permission::CreateUser->value));
        $this->assertTrue($staff->can(Permission::AssignProtectedRole->value));
        // canAccessPanel() delegates to this permission, which the super role
        // receives via Gate::before rather than an explicit grant.
        $this->assertTrue($staff->can(Permission::AccessPanel->value));
        $this->assertFalse(
            $staff->hasDirectPermission(Permission::AccessPanel->value)
        );
    }

    public function test_admin_has_user_management_but_not_protected_assignment(): void
    {
        $admin = $this->user('admin');

        $this->assertTrue($admin->can(Permission::CreateUser->value));
        $this->assertTrue($admin->can(Permission::ViewAnyUser->value));
        $this->assertTrue($admin->can(Permission::AccessPanel->value));
        $this->assertFalse($admin->can(Permission::AssignProtectedRole->value));
    }

    public function test_client_cannot_access_panel_or_manage_users(): void
    {
        $client = $this->user('client');

        $this->assertFalse($client->can(Permission::AccessPanel->value));
        $this->assertFalse($client->can(Permission::ViewAnyUser->value));
        $this->assertFalse($client->can('viewAny', User::class));
    }

    public function test_policy_is_registered_and_permission_driven(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $client));
        $this->assertFalse($client->can('update', $admin));
    }

    public function test_admin_cannot_assign_protected_role(): void
    {
        $admin = $this->user('admin');

        $this->assertTrue($admin->can('assignUserRole', [User::class, 'client']));
        $this->assertTrue($admin->can('assignUserRole', [User::class, 'admin']));
        $this->assertFalse($admin->can('assignUserRole', [User::class, 'house_trevethan_staff']));
    }

    public function test_super_role_can_assign_protected_role(): void
    {
        $staff = $this->user('house_trevethan_staff');

        $this->assertTrue($staff->can('assignUserRole', [User::class, 'house_trevethan_staff']));
    }

    public function test_admin_cannot_escalate_via_runtime_role(): void
    {
        $admin = $this->user('admin');

        // An administrator creates a new role at runtime carrying a permission
        // they do not themselves hold.
        $escalated = Role::findOrCreate('escalated', 'web');
        $escalated->givePermissionTo(
            SpatiePermission::findOrCreate(Permission::AssignProtectedRole->value, 'web')
        );

        $this->assertFalse($admin->can('assignUserRole', [User::class, 'escalated']));
    }

    public function test_admin_cannot_change_role_of_protected_user(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('house_trevethan_staff');
        $client = $this->user('client');

        $this->assertTrue($admin->can('changeUserRole', $client));
        $this->assertFalse($admin->can('changeUserRole', $staff));
    }

    public function test_unknown_role_cannot_be_assigned(): void
    {
        $admin = $this->user('admin');

        $this->assertFalse($admin->can('assignUserRole', [User::class, 'does-not-exist']));
    }

    public function test_registry_lists_assignable_roles_for_actor(): void
    {
        $admin = $this->user('admin');
        $registry = app(RoleRegistry::class);

        $assignable = $registry->assignableBy($admin);

        $this->assertArrayHasKey('client', $assignable);
        $this->assertArrayHasKey('admin', $assignable);
        $this->assertArrayNotHasKey('house_trevethan_staff', $assignable);
        $this->assertSame('Admin', $assignable['admin']);
    }

    public function test_runtime_role_receives_package_permissions(): void
    {
        $instructor = Role::findOrCreate('instructor', 'web');
        $instructor->givePermissionTo([
            Permission::AccessPanel->value,
            Permission::ViewAnyUser->value,
        ]);

        $user = $this->user('instructor');

        $this->assertTrue($user->can(Permission::AccessPanel->value));
        $this->assertTrue($user->can('viewAny', User::class));
        $this->assertFalse($user->can('create', User::class));
    }
}
