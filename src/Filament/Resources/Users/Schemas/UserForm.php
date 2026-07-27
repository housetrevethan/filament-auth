<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users\Schemas;

use Housetrevethan\FilamentAuth\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $userModel = config('filament-auth.user_model', \Housetrevethan\FilamentAuth\Models\User::class);

        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->copyable()
                            ->suffixAction(
                                Action::make('resetPassword')
                                    ->label('Reset Password')
                                    ->icon('heroicon-m-key')
                                    ->visible(fn ($record): bool => $record !== null)
                                    ->requiresConfirmation()
                                    ->modalDescription('This will send a password reset link to the user\'s email address.')
                                    ->action(function ($state) use ($userModel) {
                                        $user = $userModel::where('email', $state)->first();

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
                            ->options(UserRole::class)
                            ->default(UserRole::Core)
                            ->required(),
                        Toggle::make('has_email_authentication')
                            ->label('Email Two-Factor Authentication'),
                    ]),
            ]);
    }
}
