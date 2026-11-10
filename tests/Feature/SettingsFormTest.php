<?php

namespace Javaabu\Settings\Tests\Feature;

use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Forms\SettingsForm;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use Javaabu\Settings\Tests\TestSupport\Pages\MailSettings;
use PHPUnit\Framework\Attributes\Test;

class SettingsFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Settings::page(GeneralSettings::class);
        Settings::page(MailSettings::class);
    }

    #[Test]
    public function it_can_be_tested_with_stencils_assertable_form(): void
    {
        SettingsForm::forPage('general')
            ->assertable()
            ->assertHasField('site_name')
            ->assertState('site_name', settings('site_name'))
            ->assertFieldRequired('site_name')
            ->assertInvalid(['site_name' => ''], 'site_name');
    }

    #[Test]
    public function it_saves_the_settings_of_its_page_from_an_array(): void
    {
        $values = SettingsForm::forPage(new GeneralSettings)->saveSettings([
            'site_name' => 'ދިވެހިރާއްޖެ',
            'per_page' => '30',
            'theme' => 'dark',
            'seo_keywords' => [],
        ]);

        $this->assertSame(30, $values['per_page']);
        $this->assertEquals('ދިވެހިރާއްޖެ', settings('site_name'));
        $this->assertEquals('general', SettingsForm::forPage('general')->page()->getKey());
    }

    #[Test]
    public function it_fills_file_settings_with_their_url_and_leaves_encrypted_settings_empty(): void
    {
        Settings::set('mail_password', 'secret');

        $this->assertEquals(asset('img/logo.png'), SettingsForm::forPage('general')->currentValues()['logo']);
        $this->assertNull(SettingsForm::forPage('mail')->currentValues()['mail_password']);
    }
}
