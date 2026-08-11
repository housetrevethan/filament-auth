<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Schemas;

use Housetrevethan\FilamentAuth\Models\User;
use Housetrevethan\FilamentAuth\Support\RoleRegistry;
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
                            ->visible(fn ($record): bool => $record !== null)
                            ->state(function (?User $record): ?string {
                                if ($record === null) {
                                    return null;
                                }

                                return $record->hasOAuthProvider()
                                    ? "This user's account is managed via Microsoft. Email, Name, and 2FA are readonly/disabled."
                                    : "This user's account is a local login account.";
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
                            ->label('Role')
                            ->options(fn (): array => app(RoleRegistry::class)->assignableBy(auth()->user()))
                            ->default(fn (): ?string => config('filament-auth.roles.default'))
                            ->disabled(fn ($record): bool => ! auth()->user()?->can('changeUserRole', $record ?? User::class))
                            ->dehydrated(fn ($record): bool => (bool) auth()->user()?->can('changeUserRole', $record ?? User::class))
                            ->helperText(fn ($record): ?string => auth()->user()?->can('changeUserRole', $record ?? User::class)
                                ? null
                                : 'You do not have permission to change this user\'s role.')
                            ->required(),
                        Toggle::make('has_email_authentication')
                            ->label('Email Two-Factor Authentication')
                            ->disabled(fn ($record) => $record !== null && $record->hasOAuthProvider()),
                    ]),
            ]);
    }
}