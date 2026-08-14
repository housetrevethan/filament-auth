<?php

namespace Housetrevethan\FilamentAuth;

use Housetrevethan\FilamentAuth\Console\InstallCommand;
use Housetrevethan\FilamentAuth\Contracts\OAuthRoleProvisioner;
use Housetrevethan\FilamentAuth\Exceptions\OAuthRoleProvisionerNotBound;
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
            'services.microsoft.tenant' => 'common',
            'services.microsoft.include_tenant_info' => true,
            'services.microsoft.include_avatar' => true,
            'services.microsoft.include_avatar_size' => '648x648',
        ]);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerCommands();
        $this->guardRoleProvisionerBinding();
    }

    /**
     * Fall back to a resolver that throws a clear, actionable exception when
     * the consuming application never bound its own OAuthRoleProvisioner.
     * Runs in boot() — after every provider's register() has executed — so
     * this correctly detects the app's binding regardless of provider order.
     */
    private function guardRoleProvisionerBinding(): void
    {
        if ($this->app->bound(OAuthRoleProvisioner::class)) {
            return;
        }

        $this->app->bind(OAuthRoleProvisioner::class, function (): never {
            throw OAuthRoleProvisionerNotBound::make();
        });
    }

    private function registerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/Config/filament-auth.php' => config_path('filament-auth.php'),
        ], 'filament-auth-config');

        // The OAuth columns migration is generated into the consuming app by the
        // 'filament-auth:install' command. It is intentionally NOT autoloaded from
        // this package (no loadMigrationsFrom) — doing so caused the migration to
        // run from both the vendor dir and the published copy.
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/microsoft.php');
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
