<?php

namespace Housetrevethan\FilamentAuth;

use Housetrevethan\FilamentAuth\Console\InstallCommand;
use Housetrevethan\FilamentAuth\Models\User;
use Housetrevethan\FilamentAuth\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FilamentAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/filament-auth.php',
            'filament-auth'
        );
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

        // Full users table migration — publish-only, not auto-loaded
        $this->publishes([
            __DIR__ . '/Database/Migrations/2020_01_01_000000_create_users_table.php'
                => database_path('migrations/2020_01_01_000000_create_users_table.php'),
        ], 'filament-auth-migrations-create');

        // Additive OAuth migration — also publishable if apps want to customise
        $this->publishes([
            __DIR__ . '/Database/Migrations/2020_01_01_000001_add_oauth_columns_to_users.php'
                => database_path('migrations/2020_01_01_000001_add_oauth_columns_to_users.php'),
        ], 'filament-auth-migrations');
    }

    private function registerMigrations(): void
    {
        // Only the additive OAuth migration is auto-loaded.
        // The create_users_table migration is publish-only (via install command).
        $this->loadMigrationsFrom(
            __DIR__ . '/Database/Migrations/2020_01_01_000001_add_oauth_columns_to_users.php'
        );
    }

    private function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/microsoft.php');
    }

    private function registerPolicies(): void
    {
        // Register the policy against the consuming app's User model (convention).
        Gate::policy('App\Models\User', UserPolicy::class);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
