# housetrevethan/filament-auth

Base authentication package for House Trevethan Filament applications.

This package provides:

- Microsoft OAuth sign-in flow for Filament login pages
- Role-aware user provisioning (Core or Client based on tenant)
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
```

Important:

- Use `HOUSE_TREVETHAN_TENANT_ID` for the House Trevethan tenant UUID.

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

Ensure your consuming app has a Microsoft Socialite provider config in `config/services.php`:

```php
'microsoft' => [
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect' => env('MICROSOFT_REDIRECT_URI'),
    'tenant' => 'common',
    'include_tenant_info' => true,
    'include_avatar' => true,
    'include_avatar_size' => '648x648',
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
    // Override canAccessPanel() if your role access rules differ.
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
- `mfa.app_enabled`
- `mfa.email_enabled`
- `mfa.recovery_code_count`
- `mfa.code_expiry_minutes`

You can tune MFA behavior either through plugin method arguments (`->mfa(...)`) and/or config values.

## User Provisioning Behavior (Microsoft Login)

- Tenant must match `HOUSE_TREVETHAN_TENANT_ID` or be present in `MICROSOFT_ALLOWED_TENANT_IDS`
- New users are created with role:
  - `core` when from the House Trevethan tenant
  - `client` when from an allowed client tenant
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
