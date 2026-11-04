<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Enums\Themes;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use Javaabu\Settings\Tests\TestSupport\Pages\MailSettings;
use PHPUnit\Framework\Attributes\Test;

class SettingsRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Settings::page(GeneralSettings::class);
        Settings::page(MailSettings::class);
    }

    #[Test]
    public function it_returns_the_default_from_the_definition_when_nothing_is_stored(): void
    {
        $this->assertEquals('ރާއްޖެ', Settings::get('site_name'));
        $this->assertSame(20, Settings::get('per_page'));
        $this->assertTrue(Settings::get('registration_open'));
        $this->assertEquals(['laravel', 'javaabu'], Settings::get('seo_keywords'));
    }

    #[Test]
    public function it_falls_back_to_the_defaults_config_for_settings_without_a_definition(): void
    {
        $this->app['config']->set('defaults.app_name', 'Laravel');

        $this->assertEquals('Laravel', Settings::get('app_name'));
        $this->assertEquals('Laravel', Settings::default('app_name'));
    }

    #[Test]
    public function it_returns_the_given_default_when_a_setting_has_none(): void
    {
        $this->assertEquals('fallback', Settings::get('unknown', 'fallback'));
        $this->assertNull(Settings::get('unknown'));
    }

    #[Test]
    public function it_stores_and_reads_values(): void
    {
        Settings::set('site_name', 'މާލެ');
        Settings::set(['per_page' => 15, 'registration_open' => false]);

        $this->assertEquals('މާލެ', Settings::get('site_name'));
        $this->assertSame(15, Settings::get('per_page'));
        $this->assertFalse(Settings::get('registration_open'));
        $this->assertTrue(Settings::has('site_name'));
        $this->assertFalse(Settings::has('seo_description'));
    }

    #[Test]
    public function it_casts_values_by_their_field(): void
    {
        Settings::store()->put(['per_page' => '30', 'registration_open' => '0']);

        Settings::refresh();

        $this->assertSame(30, Settings::get('per_page'));
        $this->assertFalse(Settings::get('registration_open'));
    }

    #[Test]
    public function it_casts_enums_and_stores_their_values(): void
    {
        $this->assertSame(Themes::LIGHT, Settings::get('theme'));

        Settings::set('theme', Themes::DARK);

        $this->assertEquals('dark', Settings::stored()['theme']);
        $this->assertSame(Themes::DARK, Settings::get('theme'));
    }

    #[Test]
    public function it_has_typed_getters(): void
    {
        $this->assertEquals('ރާއްޖެ', Settings::string('site_name'));
        $this->assertSame(20, Settings::integer('per_page'));
        $this->assertSame(20.0, Settings::float('per_page'));
        $this->assertTrue(Settings::boolean('registration_open'));
        $this->assertEquals(['laravel', 'javaabu'], Settings::array('seo_keywords'));
        $this->assertEquals('fallback', Settings::string('unknown', 'fallback'));
    }

    #[Test]
    public function it_throws_when_a_typed_getter_gets_another_type(): void
    {
        $this->expectException(SettingsException::class);
        $this->expectExceptionMessage('Setting [site_name] should be of type [int], [string] given.');

        Settings::integer('site_name');
    }

    #[Test]
    public function it_forgets_values_so_settings_are_back_to_their_defaults(): void
    {
        Settings::set('site_name', 'މާލެ');

        Settings::forget('site_name');

        $this->assertFalse(Settings::has('site_name'));
        $this->assertEquals('ރާއްޖެ', Settings::get('site_name'));
    }

    #[Test]
    public function it_lists_every_setting(): void
    {
        $this->app['config']->set('defaults', ['app_name' => 'Laravel']);

        Settings::store()->put(['legacy' => 'value']);
        Settings::refresh();

        $all = Settings::all();

        $this->assertEquals('ރާއްޖެ', $all['site_name']);
        $this->assertEquals('value', $all['legacy']);
        $this->assertEquals('Laravel', $all['app_name']);
        $this->assertArrayHasKey('mail_password', $all);
    }

    #[Test]
    public function it_keeps_encrypted_settings_encrypted_in_the_store(): void
    {
        Settings::set('mail_password', 'secret');

        $stored = Settings::store()->all()['mail_password'];

        $this->assertNotEquals('secret', $stored);
        $this->assertEquals('secret', decrypt($stored));
        $this->assertEquals('secret', Settings::get('mail_password'));
    }

    #[Test]
    public function it_fires_an_event_with_the_old_and_new_values(): void
    {
        Event::fake([SettingsUpdated::class]);

        Settings::set(['site_name' => 'މާލެ', 'per_page' => 20]);

        Event::assertDispatched(SettingsUpdated::class, function (SettingsUpdated $event) {
            return $event->old_settings === ['site_name' => 'ރާއްޖެ', 'per_page' => 20]
                && $event->changes() === ['site_name' => 'މާލެ']
                && $event->changed('site_name')
                && ! $event->changed('per_page');
        });
    }

    #[Test]
    public function it_doesnt_fire_the_event_when_nothing_changed(): void
    {
        Event::fake([SettingsUpdated::class]);

        Settings::set('per_page', 20);

        Event::assertNotDispatched(SettingsUpdated::class);
    }

    #[Test]
    public function it_defines_settings_without_a_page(): void
    {
        Settings::define([
            Setting::make('skeleton_version')->default('9.0.0'),
            Setting::make('max_upload_size')->default('2048')->cast('integer'),
        ]);

        $this->assertEquals('9.0.0', Settings::get('skeleton_version'));
        $this->assertSame(2048, Settings::get('max_upload_size'));
        $this->assertArrayNotHasKey('skeleton_version', Settings::pages());
    }

    #[Test]
    public function it_evaluates_closure_defaults_each_time(): void
    {
        $this->app['config']->set('app.name', 'First');

        Settings::define(Setting::make('app_title')->default(fn () => config('app.name')));

        $this->assertEquals('First', Settings::get('app_title'));

        $this->app['config']->set('app.name', 'Second');

        $this->assertEquals('Second', Settings::get('app_title'));
    }

    #[Test]
    public function it_gives_the_url_of_file_settings(): void
    {
        $this->assertEquals(asset('img/logo.png'), Settings::url('logo'));

        Settings::set('logo', 'https://cdn.example.com/logo.png');

        $this->assertEquals('https://cdn.example.com/logo.png', Settings::url('logo'));
        $this->assertNull(Settings::url('seo_description'));
    }
}
