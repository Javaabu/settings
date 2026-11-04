<?php

namespace Javaabu\Settings\Tests\TestSupport\Pages;

use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;

class MailSettings extends SettingsPage
{
    protected ?string $permission = 'edit_mail_settings';

    protected int $order = 10;

    public function getLabel(): string
    {
        return __('Mail');
    }

    public function definition(): array
    {
        return [
            Setting::make('mail_from_address')
                ->default('info@example.com')
                ->label(__('From address'))
                ->email()
                ->required(),
            Setting::make('mail_password')
                ->encrypted()
                ->password()
                ->label(__('Password')),
        ];
    }
}
