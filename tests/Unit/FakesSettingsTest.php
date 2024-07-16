<?php

namespace Javaabu\Settings\Tests\Unit;

use Javaabu\Settings\Testing\FakesSettings;
use Javaabu\Settings\Tests\TestCase;

class FakesSettingsTest extends TestCase
{
    use FakesSettings;

    /** @test */
    public function it_can_fake_a_default_setting()
    {
        $this->setFakeDefaultSetting('daily_limit', 20);

        $this->assertEquals(20, default_setting('daily_limit'));
    }

    /** @test */
    public function it_can_fake_a_setting_with_null_default()
    {
        $this->setFakeSetting('app_name', 'fake name', null);

        $this->assertNull(default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    /** @test */
    public function it_can_fake_a_setting()
    {
        $this->setFakeSetting('app_name', 'fake name');

        $this->assertNull(default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    /** @test */
    public function it_can_fake_a_setting_with_a_default()
    {
        $this->setFakeSetting('app_name', 'fake name', 'default name');

        $this->assertEquals('default name', default_setting('app_name'));
        $this->assertEquals('fake name', get_setting('app_name'));
    }

    /** @test */
    public function it_can_handle_real_settings_along_with_fake_settings()
    {
        $this->app['config']->set('defaults.app_name', 'Actual Name');

        $this->setFakeSetting('max_size', 20);
        $this->setFakeSetting('max_limit', 23);

        $this->assertEquals(20, get_setting('max_size'));
        $this->assertEquals('Actual Name', get_setting('app_name'));
        $this->assertEquals(23, get_setting('max_limit'));
    }
}
