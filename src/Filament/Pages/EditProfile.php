<?php

namespace Housetrevethan\FilamentAuth\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    protected function isOAuthUser(): bool
    {
        return filled(Filament::auth()->user()?->oauth_provider_name);
    }

    protected function getAvatarFormComponent(): Component
    {
        return FileUpload::make('avatar')
            ->label('Avatar')
            ->image()
            ->avatar()
            ->disk('public')
            ->directory('avatars')
            ->visibility('public')
            ->hidden($this->isOAuthUser());
    }

    public function form(Schema $schema): Schema
    {
        if ($this->isOAuthUser()) {
            return $schema->components([
                TextEntry::make('microsoft_notice')
                    ->label('Account Management')
                    ->state('Your account profile is managed by Microsoft. <a href="https://myaccount.microsoft.com/" target="_blank" class="text-primary-600 hover:underline font-medium">Click here to manage your Microsoft account</a>.')
                    ->html(),
            ]);
        }

        return parent::form($schema)->components([
            $this->getAvatarFormComponent(),
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->hidden($this->isOAuthUser());
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()->hidden($this->isOAuthUser());
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()->hidden($this->isOAuthUser());
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if ($this->isOAuthUser()) {
            return null;
        }

        return parent::getMultiFactorAuthenticationContentComponent();
    }
}
