<?php

namespace Housetrevethan\FilamentAuth\Services;

use Housetrevethan\FilamentAuth\Contracts\OAuthLoginServiceContract;
use Housetrevethan\FilamentAuth\Enums\UserRole;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class MicrosoftLoginService implements OAuthLoginServiceContract
{
    private string $houseTrevethanTenantId;

    /** @var array<string> */
    private array $allowedTenantIds;

    private string $email;

    private string $name;

    private ?string $avatarUrl;

    private ?string $tenantId;

    private string $microsoftUserId;

    private ?string $token;

    public function __construct(private readonly SocialiteUser $socialiteUser)
    {
        $this->houseTrevethanTenantId = config('filament-auth.microsoft.house_trevethan_tenant_id', '');
        $this->allowedTenantIds = config('filament-auth.microsoft.allowed_tenant_ids', []);

        $this->email           = $this->socialiteUser->getEmail();
        $this->name            = $this->socialiteUser->getName();
        $this->avatarUrl       = $this->socialiteUser->getAvatar();
        $this->microsoftUserId = $this->socialiteUser->getId();
        $this->token           = $this->socialiteUser->token;
        $this->tenantId        = $this->socialiteUser->tenant['id'] ?? null;
    }

    public function handle(): RedirectResponse
    {
        Log::info("Microsoft OAuth attempt for {$this->email} from tenant {$this->tenantId}");

        if (! $this->isAllowedTenant()) {
            Log::warning("Rejected OAuth attempt — tenant not in allow list: {$this->tenantId}");

            return redirect(route('about'));
        }

        $user = $this->provisionUser();

        if ($user === null) {
            Log::info("Rejected OAuth — local account already exists for {$this->email}");

            Notification::make()
                ->title('Welcome Back! It looks like you already have an account.')
                ->warning()
                ->body('This email is already registered in the system. Please login with your password.')
                ->send();

            return redirect(route('filament.dashboard.auth.login'));
        }

        Auth::login($user);

        Notification::make()
            ->title('Welcome from the House Trevethan Team!')
            ->success()
            ->body('Enjoy your new system and feel free to reach out if you have any questions!')
            ->send();

        return redirect(route('filament.dashboard.pages.dashboard'));
    }

    private function isAllowedTenant(): bool
    {
        if ($this->tenantId === null) {
            return false;
        }

        return $this->tenantId === $this->houseTrevethanTenantId
            || in_array($this->tenantId, $this->allowedTenantIds);
    }

    private function assignRole(): UserRole
    {
        return $this->tenantId === $this->houseTrevethanTenantId
            ? UserRole::Core
            : UserRole::Client;
    }

    /**
     * Creates a new user or updates an existing OAuth user.
     * Returns null when the email belongs to a local (non-OAuth) account.
     */
    private function provisionUser(): ?\Housetrevethan\FilamentAuth\Models\User
    {
        $userModel = config('filament-auth.user_model', \Housetrevethan\FilamentAuth\Models\User::class);
        $existing  = $userModel::where('email', $this->email)->first();

        // Brand new user — create and assign role based on tenant
        if ($existing === null) {
            Log::info("Provisioning new OAuth user: {$this->email} as {$this->assignRole()->value}");

            return $userModel::create([
                'name'                    => $this->name,
                'email'                   => $this->email,
                'role'                    => $this->assignRole(),
                'oauth_provider_name'     => 'microsoft',
                'oauth_provider_id'       => $this->tenantId,
                'oauth_provider_user_id'  => $this->microsoftUserId,
                'email_verified_at'       => now(),
                'remember_token'          => hash('sha256', $this->token ?? ''),
                'password'                => Hash::make(Str::random(40)),
                'avatar'                  => $this->avatarUrl,
            ]);
        }

        // Returning OAuth user — refresh profile
        if ($existing->oauth_provider_user_id !== null) {
            Log::info("Updating returning OAuth user: {$this->email}");

            $existing->update([
                'name'                   => $this->name,
                'avatar'                 => $this->avatarUrl,
                'oauth_provider_id'      => $this->tenantId,
                'oauth_provider_user_id' => $this->microsoftUserId,
                'remember_token'         => hash('sha256', $this->token ?? ''),
                'password'               => Hash::make(Str::random(40)),
            ]);

            return $existing;
        }

        // Email exists as a local account — reject SSO
        return null;
    }
}
