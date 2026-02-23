<?php

namespace Javaabu\Settings\Tests\Feature;

use Javaabu\Settings\Tests\TestCase;

class JsonSettingsDriverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('settings.store', 'json');
        $this->app['config']->set('settings.defaults.foo', 'bar');
    }

    public function test_it_can_get_app_name_setting()
    {
        $this->assertEquals('Laravel', get_setting('app_name'));
        $this->assertEquals('img/logo.png', get_setting('app_logo'));
        $this->assertEquals(2048, get_setting('max_upload_file_size'));
        $this->assertEquals(5, get_setting('max_num_files'));
    }
}
