<?php

namespace Housetrevethan\FilamentAuth;

use Housetrevethan\FilamentAuth\Console\InstallCommand;
use Housetrevethan\FilamentAuth\Console\SyncRolesCommand;
use Housetrevethan\FilamentAuth\Support\RoleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FilamentAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/filament-auth.php',
            'filament-auth'
        );

        // Push required Socialite settings into services.microsoft so clients
        // don't need to set them manually — without these, Socialite won't
        // return tenant info or avatars.
        config([
            'services.microsoft.tenant'               => 'common',
            'services.microsoft.include_tenant_info'  => true,
            'services.microsoft.include_avatar'       => true,
            'services.microsoft.include_avatar_size'  => '648x648',
        ]);

        $this->app->singleton(RoleRegistry::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerCommands();
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/Config/filament-auth.php' => config_path('filament-auth.php'),
        ], 'filament-auth-config');

        // Migrations are published with dynamic timestamps via the install command.
        // These publish tags remain for manual/advanced use only.
        $this->publishes([
            __DIR__ . '/Database/Migrations/create_users_table.php'
                => database_path('migrations/' . now()->format('Y_m_d_His') . '_create_users_table.php'),
        ], 'filament-auth-migrations-create');

        $this->publishes([
            __DIR__ . '/Database/Migrations/auto/add_oauth_columns_to_users.php'
                => database_path('migrations/' . now()->addSecond()->format('Y_m_d_His') . '_add_oauth_columns_to_users.php'),
        ], 'filament-auth-migrations');
    }

    private function registerMigrations(): void
    {
        // Only the additive OAuth migration is auto-loaded.
        // The create_users_table migration is publish-only (via install command).
        $this->loadMigrationsFrom(
            __DIR__ . '/Database/Migrations/auto'
        );
    }
    private function registerRoutes(): void
    {
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/microsoft.php');
        });
    }

    private function registerPolicies(): void
    {
        $userModel = config('filament-auth.user_model', 'App\Models\User');
        $userPolicy = config('filament-auth.policies.user');

        if ($userPolicy !== null) {
            Gate::policy($userModel, $userPolicy);
        }

        // The super role bypasses every check. Returning null (rather than
        // false) for everyone else leaves the remaining gates and policies —
        // including spatie's own permission check — free to run.
        Gate::before(function ($user, string $ability) {
            $super = config('filament-auth.roles.super');

            if (blank($super) || ! method_exists($user, 'hasRole')) {
                return null;
            }

            return $user->hasRole($super) ? true : null;
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SyncRolesCommand::class,
            ]);
        }
    }
}
