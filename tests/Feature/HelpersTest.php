<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Storage;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\SettingsRepository;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Settings::page(GeneralSettings::class);
    }

    #[Test]
    public function it_reads_and_writes_with_the_settings_helper(): void
    {
        $this->assertInstanceOf(SettingsRepository::class, settings());
        $this->assertEquals('ރާއްޖެ', settings('site_name'));
        $this->assertEquals('fallback', settings('unknown', 'fallback'));

        settings(['site_name' => 'މާލެ']);

        $this->assertEquals('މާލެ', settings('site_name'));
        $this->assertSame(20, settings()->integer('per_page'));
    }

    #[Test]
    public function it_keeps_the_v1_helpers(): void
    {
        $this->app['config']->set('defaults.app_name', 'Laravel');

        $this->assertEquals('Laravel', get_setting('app_name'));
        $this->assertEquals('Laravel', default_setting('app_name'));
        $this->assertEquals('ރާއްޖެ', default_setting('site_name'));

        Settings::set('site_name', 'މާލެ');

        $this->assertEquals('މާލެ', get_setting('site_name'));
        $this->assertEquals('ރާއްޖެ', default_setting('site_name'));
        $this->assertEquals('/logo.png', remove_prefix('storage', 'storage/logo.png'));
        $this->assertEquals('img/logo.png', remove_prefix('storage', 'img/logo.png'));
    }

    #[Test]
    public function it_sets_and_resets_file_settings_from_the_request_like_v1(): void
    {
        Storage::fake('public');

        $directory = 'javaabu-settings-test-' . uniqid();

        $this->app['request']->files->set('logo', UploadedFile::fake()->image('logo.png'));

        $this->assertTrue(set_file_setting('logo', $directory));

        $path = get_setting('logo');

        $this->assertStringStartsWith("{$directory}/custom-logo-", $path);
        $this->assertFileExists(public_path($path));

        unlink(public_path($path));
        rmdir(public_path($directory));

        Settings::set('logo', 'storage/settings/logo.png');
        Storage::disk('public')->put('settings/logo.png', 'png');

        $this->app->instance('request', Request::create('/', 'POST', ['logo' => '']));
        RequestFacade::clearResolvedInstance('request');

        $this->assertTrue(set_file_setting('logo'));

        Storage::disk('public')->assertMissing('settings/logo.png');
        $this->assertEquals('img/logo.png', get_setting('logo'));
        $this->assertFalse(Settings::has('logo'));
    }

    #[Test]
    public function it_does_nothing_when_the_request_has_no_file_setting(): void
    {
        $this->assertFalse(set_file_setting('logo'));

        reset_file_setting('logo');

        $this->assertEquals('img/logo.png', get_setting('logo'));
    }
}
