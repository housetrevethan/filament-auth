<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Schemas;

use App\Models\User;
use Housetrevethan\FilamentAuth\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextEntry::make('Account Type')
                            ->state(function (User $user) {
                                $oAuthMessage = "This user's account is managed via Microsoft. Email, Name, and 2FA are readonly/disabled.";
                                $localMessage = "This user's account is a local login account.";
                                if ($user->hasOAuthProvider()) {
                                    return $oAuthMessage;
                                }

                                return $localMessage;
                            }),
                        TextInput::make('name')
                            ->required()
                            ->readOnly(fn ($record) => $record !== null && $record->hasOAuthProvider()),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->readOnly(fn ($record) => $record !== null && $record->hasOAuthProvider())
                            ->required()
                            ->copyable()
                            ->suffixAction(
                                Action::make('resetPassword')
                                    ->label('Reset Password')
                                    ->icon('heroicon-m-key')
                                    ->visible(fn ($record): bool => $record !== null)
                                    ->requiresConfirmation()
                                    ->modalDescription('This will send a password reset link to the user\'s email address.')
                                    ->action(function ($state) {
                                        $user = User::where('email', $state)->first();
                                        if (! $user) {
                                            Notification::make()
                                                ->title('User not found')
                                                ->danger()
                                                ->send();

                                            return;
                                        }
                                        Password::sendResetLink(['email' => $user->email]);
                                        Notification::make()
                                            ->title('Password reset link sent')
                                            ->success()
                                            ->send();
                                    })
                                    ->disabled(fn ($record) => $record !== null && $record->hasOAuthProvider())
                            ),
                        TextInput::make('password')
                            ->default(Str::password(12))
                            ->revealable()
                            ->required()
                            ->password()
                            ->visibleOn('create')
                            ->dehydrateStateUsing(fn (string $state): string => bcrypt($state)),
                    ]),
                Section::make('Access')
                    ->schema([
                        Select::make('role')
                            ->options(fn (): array => self::availableRoles())
                            ->default(UserRole::Client)
                            ->disabled(fn ($record): bool => ! auth()->user()?->can('changeUserRole', $record ?? User::class))
                            ->dehydrated(fn ($record): bool => (bool) auth()->user()?->can('changeUserRole', $record ?? User::class))
                            ->helperText(fn ($record): ?string => auth()->user()?->can('changeUserRole', $record ?? User::class)
                                ? null
                                : 'Only House Trevethan staff can change this user\'s role.')
                            ->required(),
                        Toggle::make('has_email_authentication')
                            ->label('Email Two-Factor Authentication')
                            ->disabled(fn ($record) => $record !== null && $record->hasOAuthProvider()),
                    ]),
            ]);
    }

    /**
     * Role options the acting user is permitted to assign.
     *
     * @return array<string, string>
     */
    protected static function availableRoles(): array
    {
        $actor = auth()->user();

        return collect(UserRole::cases())
            ->filter(fn (UserRole $role): bool => (bool) $actor?->can('assignUserRole', [User::class, $role]))
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->getLabel()])
            ->all();
    }
}