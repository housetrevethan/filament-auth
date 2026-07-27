<?php

namespace Housetrevethan\FilamentAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'filament-auth:install';

    protected $description = 'Install the filament-auth package into this application';

    public function handle(): int
    {
        $this->info('Installing housetrevethan/filament-auth...');
        $this->newLine();

        $this->publishConfig();
        $this->publishMigrations();
        $this->printModelChecklist();
        $this->printPanelChecklist();

        $this->newLine();
        $this->info('Installation complete!');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $this->info('→ Publishing config...');

        $this->callSilently('vendor:publish', [
            '--tag'   => 'filament-auth-config',
            '--force' => false,
        ]);

        $this->line('  Config published to <comment>config/filament-auth.php</comment>');
    }

    private function publishMigrations(): void
    {
        $this->info('→ Checking database state...');

        if (Schema::hasTable('users')) {
            $this->line('  Existing <comment>users</comment> table detected.');
            $this->line('  Publishing additive OAuth migration only...');

            $this->callSilently('vendor:publish', [
                '--tag'   => 'filament-auth-migrations',
                '--force' => false,
            ]);

            $this->line('  Migration published: <comment>add_oauth_columns_to_users</comment>');
            $this->newLine();
            $this->warn('  ⚠  Verify your existing users table has these columns:');
            $this->line('     - avatar (text, nullable)');
            $this->line('     - role (string, default: "core")');
            $this->line('     - app_authentication_secret (text, nullable)');
            $this->line('     - app_authentication_recovery_codes (text, nullable)');
            $this->line('     - has_email_authentication (boolean, default: false)');
            $this->line('     If any are missing, add them manually before running migrate.');
        } else {
            $this->line('  No <comment>users</comment> table found.');
            $this->line('  Publishing full users table migration...');

            $this->callSilently('vendor:publish', [
                '--tag'   => 'filament-auth-migrations-create',
                '--force' => false,
            ]);

            $this->callSilently('vendor:publish', [
                '--tag'   => 'filament-auth-migrations',
                '--force' => false,
            ]);

            $this->line('  Migrations published. Run <comment>php artisan migrate</comment> to apply.');
        }
    }

    private function printModelChecklist(): void
    {
        $this->newLine();
        $this->info('→ Update your App\Models\User model:');
        $this->newLine();
        $this->line('  Replace the class body with:');
        $this->newLine();
        $this->line('  <comment>class User extends \Housetrevethan\FilamentAuth\Models\User</comment>');
        $this->line('  <comment>{</comment>');
        $this->line('  <comment>    // App-specific additions only.</comment>');
        $this->line('  <comment>    // Override canAccessPanel() here if your role logic differs.</comment>');
        $this->line('  <comment>}</comment>');
        $this->newLine();
        $this->line('  The base model provides all fillable fields, casts, MFA methods,');
        $this->line('  avatar helper, and Filament interface implementations automatically.');
    }

    private function printPanelChecklist(): void
    {
        $this->newLine();
        $this->info('→ Register the plugin in your PanelProvider:');
        $this->newLine();
        $this->line('  <comment>use Housetrevethan\FilamentAuth\FilamentAuthPlugin;</comment>');
        $this->newLine();
        $this->line('  <comment>->plugins([</comment>');
        $this->line('  <comment>    FilamentAuthPlugin::make()</comment>');
        $this->line('  <comment>        ->mfa(app: true, email: true)</comment>');
        $this->line('  <comment>        ->microsoftOAuth()</comment>');
        $this->line('  <comment>        ->userResource()</comment>');
        $this->line('  <comment>        ->editProfile(),</comment>');
        $this->line('  <comment>])</comment>');
        $this->newLine();
        $this->info('→ Add to your .env:');
        $this->line('  HOUSE_TREVETHAN_TENANT_ID=<your-ht-tenant-uuid>');
        $this->line('  MICROSOFT_ALLOWED_TENANT_IDS=<client-uuid-1>,<client-uuid-2>');
        $this->line('  MICROSOFT_CLIENT_ID=<azure-app-client-id>');
        $this->line('  MICROSOFT_CLIENT_SECRET=<azure-app-client-secret>');
        $this->line('  MICROSOFT_REDIRECT_URI=${APP_URL}/auth/microsoft/callback');
    }
}
