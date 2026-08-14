# housetrevethan/filament-auth

Base authentication package for House Trevethan Filament applications.

Provides:

- Microsoft OAuth sign-in on Filament login pages
- Tenant-aware user provisioning for the House Trevethan tenant and allowed tenants
- Filament multi-factor authentication (authenticator app and/or email)
- A role/permission provisioning hook (`OAuthRoleProvisioner`) you implement in your app

## Requirements

- PHP 8.4+
- Laravel 12+
- Filament 5+

## Installation

### 1) Add the repository

In the consuming app's `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/housetrevethan/filament-auth.git" }
  ],
  "require": {
    "housetrevethan/filament-auth": "^1.0"
  }
}
```

Then require it:

```bash
composer require housetrevethan/filament-auth
```

### 2) Run the install command

```bash
php artisan filament-auth:install
```

This:

- Publishes config to `config/filament-auth.php`
- **Generates** an `add_oauth_columns_to_users` migration into `database/migrations`
- Prints the users-table columns you must add, plus the User model and panel setup

> The package does **not** publish a users-table migration and does **not** autoload the
> OAuth migration from the vendor directory — it is generated once into your app so
> Laravel never runs it from two places.

### 3) Add the required users-table columns

The package needs these columns on your existing `create_users_table` migration. Add any
that are missing:

```php
$table->text('avatar')->nullable();
$table->text('app_authentication_secret')->nullable();
$table->text('app_authentication_recovery_codes')->nullable();
$table->boolean('has_email_authentication')->default(false);
$table->timestamp('email_verified_at')->nullable();
```

The generated migration adds the OAuth columns (`oauth_provider_name`, `oauth_provider_id`,
`oauth_provider_user_id`) and a unique identity index on top of that table.

### 4) Migrate

```bash
php artisan migrate
```

## Configuration

### Environment variables

```dotenv
HOUSE_TREVETHAN_TENANT_ID=<house-trevethan-tenant-uuid>
MICROSOFT_ALLOWED_TENANT_IDS=<client-tenant-uuid-1>,<client-tenant-uuid-2>
MICROSOFT_CLIENT_ID=<azure-app-client-id>
MICROSOFT_CLIENT_SECRET=<azure-app-client-secret>
MICROSOFT_REDIRECT_URI=${APP_URL}/auth/microsoft/callback

FILAMENT_DASHBOARD_ROUTE=<dashboard-route-name>   # redirect on successful login
FILAMENT_LOGIN_ROUTE=<login-route-name>           # redirect on local-account conflict
FAILED_LOGIN_REDIRECT=<denied-route-name>         # redirect on denied tenant

# Optional MFA overrides (defaults shown)
MFA_APP_ENABLED=true
MFA_EMAIL_ENABLED=true
MFA_RECOVERY_CODE_COUNT=8
MFA_CODE_EXPIRY_MINUTES=5
```

`FILAMENT_DASHBOARD_ROUTE`, `FILAMENT_LOGIN_ROUTE`, and `FAILED_LOGIN_REDIRECT` must be valid
named routes — the OAuth controller redirects to them after success, conflict, and denial.

### services.php (Microsoft)

Add **only** the three client-specific values. The package sets `tenant`,
`include_tenant_info`, `include_avatar`, and `include_avatar_size` at boot — do not override
them:

```php
'microsoft' => [
    'client_id'     => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect'      => env('MICROSOFT_REDIRECT_URI'),
],
```

### Publish config manually (optional)

```bash
php artisan vendor:publish --tag=filament-auth-config
```

## Usage

### 1) Install the Microsoft Socialite provider

```bash
composer require socialiteproviders/microsoft
```

Register the driver in `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

Event::listen(function (SocialiteWasCalled $event) {
    $event->extendSocialite('microsoft', Provider::class);
});
```

### 2) Register the plugin in your panel provider

```php
use Housetrevethan\FilamentAuth\FilamentAuthPlugin;

->plugins([
    FilamentAuthPlugin::make()
        ->mfa(app: true, email: true)   // toggle authenticator-app / email MFA
        ->microsoftOAuth(),             // render the "Sign in with Microsoft" button
])
```

### 3) Extend the package User model

`app/Models/User.php`:

```php
<?php

namespace App\Models;

class User extends \Housetrevethan\FilamentAuth\Models\User
{
    // App-specific relationships/methods only.
    // Override canAccessPanel() here — this package does not handle roles/permissions.
}
```

The base model provides fillable fields, casts, MFA methods, the avatar helper, and the
Filament interface implementations.

> **Panel access.** The base `canAccessPanel()` returns `$this->can('access panel')`, so
> **every login is denied until your app defines that ability** (a Gate or a permission via
> e.g. spatie/laravel-permission). Override `canAccessPanel()` in your model if your access
> rules differ.

### 4) Bind an `OAuthRoleProvisioner` (required)

> **Required.** This package does not handle roles or permissions. It resolves
> `Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner` from the container and calls
> `provisionRoles()` immediately after a new OAuth user is created. If you don't bind an
> implementation, the first Microsoft sign-in that creates a user throws
> `OAuthRoleProvisionerNotBound`.

Create an implementation:

```php
<?php

namespace App\Auth;

use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;

class RoleProvisioner implements OAuthRoleProvisioner
{
    /**
     * @param array{
     *     email: string,
     *     name: string,
     *     avatar: ?string,
     *     oauth-user-id: string,
     *     oauth-provider-id: ?string,
     *     oauth-provider-token: string,
     *     oauth-provider-name: string,
     * } $oauthUserData
     */
    public function provisionRoles(array $oauthUserData): void
    {
        // Assign roles/permissions/initial state for the newly created user,
        // e.g. via spatie/laravel-permission or your own logic.
    }
}
```

Bind it in `AppServiceProvider::register()`:

```php
use App\Auth\RoleProvisioner;
use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;

$this->app->bind(OAuthRoleProvisioner::class, RoleProvisioner::class);
```

`provisionRoles()` runs **once**, right after the account is created — not on subsequent
logins.

### OAuth routes

The package registers (under the `web` middleware):

- `GET /auth/microsoft/redirect` → `auth.microsoft.redirect`
- `GET /auth/microsoft/callback` → `auth.microsoft.callback`

When `->microsoftOAuth()` is enabled, the "Sign in with Microsoft" button on the Filament
login page points at the redirect route.

## Provisioning behavior (Microsoft login)

- Tenant must equal `HOUSE_TREVETHAN_TENANT_ID` or appear in `MICROSOFT_ALLOWED_TENANT_IDS`.
  The House Trevethan tenant is always permitted without being added to the allowlist.
- New users from an allowed tenant are provisioned on first login.
- Existing OAuth users are updated on login.
- Existing local (non-OAuth) users with the same email are **not** converted to OAuth.

## Config reference

`config/filament-auth.php`:

- `microsoft.house_trevethan_tenant_id`, `microsoft.allowed_tenant_ids`
- `mfa.app_enabled`, `mfa.email_enabled`, `mfa.recovery_code_count`, `mfa.code_expiry_minutes`

MFA can be tuned via `->mfa(...)` arguments and/or these config values.

## Troubleshooting

- **Signed in but "403 / cannot access panel"** — define the `access panel` ability, or override `canAccessPanel()` (see Usage step 3).
- **`OAuthRoleProvisionerNotBound`** — bind an `OAuthRoleProvisioner` implementation (see Usage step 4).
- **"Tenant not allowed"** — verify `HOUSE_TREVETHAN_TENANT_ID` / `MICROSOFT_ALLOWED_TENANT_IDS`.
- **OAuth redirect mismatch** — the Azure app redirect URI must equal `MICROSOFT_REDIRECT_URI`.
- **No Microsoft button** — ensure `->microsoftOAuth()` is in the panel plugin chain.
- **Route not found** — clear caches and reload routes:

```bash
php artisan optimize:clear
php artisan route:list | Select-String microsoft
```
