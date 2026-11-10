<?php

namespace Javaabu\Settings\Tests\Unit;

use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Testing\FakesSettings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;

class FakesSettingsTest extends TestCase
{
    use FakesSettings;

    #[Test]
    public function it_can_fake_a_default_setting(): void
    {
        $this->setFakeDefaultSetting('daily_limit', 20);

        $this->assertEquals(20, default_setting('daily_limit'));
    }

    #[Test]
    public function it_can_fake_a_setting_with_null_default(): void
    {
        $this->setFakeSetting('app_name', 'fake name', null);

        $this->assertNull(default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    #[Test]
    public function it_can_fake_a_setting(): void
    {
        $this->setFakeSetting('app_name', 'fake name');

        $this->assertNull(default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    #[Test]
    public function it_can_fake_a_setting_with_a_default(): void
    {
        $this->setFakeSetting('app_name', 'fake name', 'default name');

        $this->assertEquals('default name', default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    #[Test]
    public function it_can_handle_real_settings_along_with_fake_settings(): void
    {
        $this->app['config']->set('defaults.app_name', 'Actual Name');

        $this->setFakeSetting('max_size', 20);
        $this->setFakeSetting('max_limit', 23);

        $this->assertEquals(20, get_setting('max_size'));
        $this->assertEquals('Actual Name', get_setting('app_name'));
        $this->assertEquals(23, get_setting('max_limit'));
    }

    #[Test]
    public function it_fakes_defined_settings_without_touching_the_store(): void
    {
        Settings::page(GeneralSettings::class);

        Settings::set('site_name', 'Stored');

        $this->setFakeSettings(['site_name' => 'ދިވެހިރާއްޖެ', 'per_page' => '5']);
        $this->setFakeDefaultSetting('registration_open', false);

        $this->assertTrue(Settings::isFaking());
        $this->assertEquals('ދިވެހިރާއްޖެ', settings('site_name'));
        $this->assertSame(5, settings('per_page'));
        $this->assertFalse(settings('registration_open'));

        Settings::stopFaking();

        $this->assertEquals('Stored', settings('site_name'));
        $this->assertTrue(settings('registration_open'));
    }
}
