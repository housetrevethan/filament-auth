<?php
declare(strict_types = 1);

namespace Housetrevethan\FilamentAuth\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filament-auth:install';

    protected $description = 'Install the filament-auth package into this application';

    public function handle(): int
    {
        $this->info('Installing housetrevethan/filament-auth...');
        $this->newLine();

        $this->publishConfig();
        $this->printUsersTableChecklist();
        $this->generateOAuthMigration();
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
            '--tag' => 'filament-auth-config',
            '--force' => false,
        ]);

        $this->line('  Config published to <comment>config/filament-auth.php</comment>');
    }

    private function printUsersTableChecklist(): void
    {
        $this->newLine();
        $this->info('→ Ensure your users table migration includes these columns:');
        $this->line('     - avatar (text, nullable)');
        $this->line('     - app_authentication_secret (text, nullable)');
        $this->line('     - app_authentication_recovery_codes (text, nullable)');
        $this->line('     - has_email_authentication (boolean, default: false)');
        $this->newLine();
        $this->line('  Add any missing columns to your <comment>create_users_table</comment> migration');
        $this->line('  before running <comment>php artisan migrate</comment>.');
    }

    private function generateOAuthMigration(): void
    {
        $this->newLine();
        $this->info('→ Generating OAuth columns migration...');

        if ($this->findExistingMigration('add_oauth_columns_to_users')) {
            $this->line('  OAuth migration already exists, skipping.');

            return;
        }

        $filename = now()->format('Y_m_d_His') . '_add_oauth_columns_to_users.php';
        $destination = database_path("migrations/$filename");

        copy(
            __DIR__ . '/../Database/Stubs/add_oauth_columns_to_users.stub',
            $destination
        );

        $this->line("  Migration generated: <comment>database/migrations/$filename</comment>");
    }

    private function findExistingMigration(string $name): ?string
    {
        foreach (glob(database_path("migrations/*_$name.php")) as $file) {
            return basename($file);
        }

        return null;
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
        $this->line('  <comment>    // Override canAccessPanel() here! This package does not handle roles or permissions.</comment>');
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
