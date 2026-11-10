<?php

namespace Javaabu\Settings\Tests\TestSupport\Pages;

use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Stencil\Fields\Textarea;

/**
 * Translatable settings, for the javaabu/translatable tests
 */
class SiteSettings extends SettingsPage
{
    public function definition(): array
    {
        return [
            Setting::make('site_title')
                ->default('Maldives')
                ->label(__('Site title'))
                ->required()
                ->maxLength(100)
                ->translatable(),
            Setting::make('footer_text')
                ->field(Textarea::class)
                ->label(__('Footer'))
                ->translatable(),
            Setting::make('contact_email')
                ->default('info@example.com')
                ->email(),
        ];
    }
}
