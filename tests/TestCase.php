<?php

namespace Javaabu\Settings\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Javaabu\Settings\SettingsServiceProvider;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');

        $this->app['config']->set('session.serialization', 'php');
    }

    protected function getPackageProviders($app): array
    {
        return [SettingsServiceProvider::class];
    }
}
