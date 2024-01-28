<?php

namespace Javaabu\Settings\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Javaabu\Settings\SettingsServiceProvider;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

abstract class JsonDriverTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('settings.store', 'json');
        $this->app['config']->set('settings.defaults.foo', 'bar');
    }
}
