# housetrevethan/filament-auth

Base authentication package for House Trevethan Filament applications.

This package provides:

- Microsoft OAuth sign-in flow for Filament login pages
- Extensible roles and permissions, backed by spatie/laravel-permission
- Role-aware user provisioning based on the Microsoft tenant
- Filament multi-factor authentication (app and/or email)
- Optional bundled User resource and profile page integration

## Requirements

- PHP 8.4+
- Laravel 12+
- Filament 5+

## Install As A Private GitHub Package (SSH)

In your consuming Laravel app, add this package repository to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:HouseTrevethanProjects/filament-auth.git"
    }
  ],
  "require": {
    "housetrevethan/filament-auth": "^1.0"
  }
}
```

Then install:

```bash
composer update housetrevethan/filament-auth
```

Notes:

- Your machine/CI runner must have SSH access to the private repo.
- Typical CI setup is an SSH deploy key + `known_hosts` entry for GitHub.

## Package Installation In Your App

Run the install command:

```bash
php artisan filament-auth:install
```

This command:

- Publishes package config to `config/filament-auth.php`
- Publishes migrations (full users table when absent, additive OAuth migration otherwise)
- Prints the expected User model and Filament panel plugin setup

Run migrations after publishing:

```bash
php artisan migrate
```

## Publish Config Manually (Optional)

If you want to publish config yourself:

```bash
php artisan vendor:publish --tag=filament-auth-config
```

Migration publish tags:

```bash
# Full users table migration (publish-only)
php artisan vendor:publish --tag=filament-auth-migrations-create

# Additive OAuth columns migration
php artisan vendor:publish --tag=filament-auth-migrations
```

## Environment Variables

Add these values to your app's `.env`:

```dotenv
HOUSE_TREVETHAN_TENANT_ID=<your-house-trevethan-tenant-uuid>
MICROSOFT_ALLOWED_TENANT_IDS=<client-tenant-uuid-1>,<client-tenant-uuid-2>
MICROSOFT_CLIENT_ID=<azure-app-client-id>
MICROSOFT_CLIENT_SECRET=<azure-app-client-secret>
MICROSOFT_REDIRECT_URI=${APP_URL}/auth/microsoft/callback

FILAMENT_DASHBOARD_ROUTE=<your-filament-dashboard-route-name>
FILAMENT_LOGIN_ROUTE=<your-filament-login-route-name>
FAILED_LOGIN_REDIRECT=<route-name-for-denied-logins>
```

Important:

- Use `HOUSE_TREVETHAN_TENANT_ID` for the House Trevethan tenant UUID.
- `FILAMENT_DASHBOARD_ROUTE`, `FILAMENT_LOGIN_ROUTE`, and `FAILED_LOGIN_REDIRECT` must be valid named routes in your app. The OAuth controller uses these to redirect after login success, local-account conflict, and denied tenant, respectively.

## Microsoft Socialite Provider

Install the Microsoft Socialite provider:

```bash
composer require socialiteproviders/microsoft
```

Register the driver in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

public function boot(): void
{
    Event::listen(function (SocialiteWasCalled $event) {
        $event->extendSocialite('microsoft', Provider::class);
    });
}
```

## services.php Setup (Microsoft OAuth)

Ensure your consuming app has a Microsoft Socialite provider config in `config/services.php` with **only** the three client-specific values. The package automatically sets `tenant`, `include_tenant_info`, `include_avatar`, and `include_avatar_size` at boot — do not override them:

```php
'microsoft' => [
    'client_id'     => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect'      => env('MICROSOFT_REDIRECT_URI'),
],
```

## Basic Usage

### 1) Register the plugin in your Filament panel

In your panel provider:

```php
use Housetrevethan\FilamentAuth\FilamentAuthPlugin;

->plugins([
    FilamentAuthPlugin::make()
        ->mfa(app: true, email: true)
        ->microsoftOAuth()
        ->userResource()
        ->editProfile(),
])
```

### 2) Extend the package User model

In your app `app/Models/User.php`:

```php
<?php

namespace App\Models;

class User extends \Housetrevethan\FilamentAuth\Models\User
{
    // Add app-specific relationships or methods here.
    // The base model already applies spatie's HasRoles trait.
}
```

### 3) OAuth routes provided by this package

The package registers:

- `GET /auth/microsoft/redirect` (route name: `auth.microsoft.redirect`)
- `GET /auth/microsoft/callback` (route name: `auth.microsoft.callback`)

When `->microsoftOAuth()` is enabled, a "Sign in with Microsoft" action is rendered on the Filament login page and points to the redirect route above.

## Configuration Reference

Published `config/filament-auth.php` includes:

- `microsoft.house_trevethan_tenant_id`
- `microsoft.allowed_tenant_ids`
- `user_model` / `policies.user`
- `roles.super` / `roles.default` / `roles.protected` / `roles.definitions` / `roles.oauth`
- `mfa.app_enabled`
- `mfa.email_enabled`
- `mfa.recovery_code_count`
- `mfa.code_expiry_minutes`

You can tune MFA behavior either through plugin method arguments (`->mfa(...)`) and/or config values.

## Roles & Permissions

Authorization is permission-based. Package code never checks a role name — it
checks a permission — so applications can add their own roles freely.

Permissions shipped by the package are listed in
`Housetrevethan\FilamentAuth\Enums\Permission`:

`panel.access`, `users.viewAny`, `users.view`, `users.create`, `users.update`,
`users.delete`, `users.restore`, `users.forceDelete`, `users.editProfile`,
`users.changeRole`, `roles.manage`, `roles.assignProtected`.

### Default roles

| Role | Permissions |
| --- | --- |
| `house_trevethan_staff` | All (super role — bypasses every check) |
| `admin` | Panel access and full user management, except assigning protected roles |
| `client` | None |

Publish the permission tables, migrate, then sync the code-defined defaults:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan filament-auth:sync-roles
```

`filament-auth:install` performs the publish step for you. If the `roles` table
is missing at runtime you will see `relation "roles" does not exist` — that
means the publish or migrate step above has not run.
The sync is additive by default, so permissions granted to a role at runtime
survive a deploy. Use `--prune` to make the database match config exactly.

### Adding roles in code

Add an entry to `roles.definitions` in `config/filament-auth.php`, then re-run
`filament-auth:sync-roles`:

```php
'instructor' => [
    'label' => 'Instructor',
    'permissions' => [
        'panel.access',
        'users.viewAny',
    ],
],
```

Applications may also invent their own permission keys here — the sync command
creates any permission referenced by a role definition.

### Adding roles at runtime

Roles are ordinary spatie models, so they can be created and edited by an admin
UI at any time. They immediately work with the package's user resource:

```php
use Spatie\Permission\Models\Role;

Role::findOrCreate('reviewer', 'web')->givePermissionTo('panel.access');
```

### Protected roles and privilege escalation

Roles listed in `roles.protected` (and the `roles.super` role) can only be
granted by a user holding `roles.assignProtected`.

Beyond that, a user may never assign a role carrying a permission they do not
themselves hold. This prevents anyone with role-management access from creating
a powerful role and promoting themselves into it.

### Replacing the policy entirely

If the permission model is not enough, point `policies.user` at your own class,
or set it to `null` to register nothing.

## Upgrading from the enum-based roles

Earlier versions stored a single role string on `users.role` and used the
`UserRole` enum. That column is migrated onto the permission tables and dropped
automatically. Note these breaking changes:

- `$user->role` no longer exists. Use `$user->hasRole('admin')`,
  `$user->primaryRoleName()`, or a permission check.
- `canAccessPanel()` now checks the `panel.access` permission.
- `UserRole` remains only as a convenience reference to the built-in role names.

## User Provisioning Behavior (Microsoft Login)

- Tenant must match `HOUSE_TREVETHAN_TENANT_ID` or be present in `MICROSOFT_ALLOWED_TENANT_IDS`
- New users are created with the role mapped in `filament-auth.roles.oauth`:
  - `house_trevethan_staff` when from the House Trevethan tenant
  - `admin` when from an allowed client tenant
  - `client` otherwise
- Existing OAuth users are updated on login
- Existing local (non-OAuth) users with the same email are not converted to OAuth accounts

## Troubleshooting

- "Tenant not allowed": verify `HOUSE_TREVETHAN_TENANT_ID` and `MICROSOFT_ALLOWED_TENANT_IDS`
- OAuth redirect mismatch: confirm Azure app redirect URI equals `MICROSOFT_REDIRECT_URI`
- No Microsoft login button: ensure `->microsoftOAuth()` is enabled in your panel plugin chain
- Route not found: clear caches and reload routes:

```bash
php artisan optimize:clear
php artisan route:list | grep microsoft
```
