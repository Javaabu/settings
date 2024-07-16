<?php

namespace Javaabu\Settings\Testing;

use anlutro\LaravelSettings\Facades\Setting;

trait FakesSettings
{
    protected array $fake_settings = [];
    /**
     * Fake a setting default
     */
    protected function setFakeDefaultSetting(string $setting, $value)
    {
        $this->app['config']->set('defaults.' . $setting, $value);
    }

    /**
     * Fake a setting
     */
    protected function setFakeSetting(string $setting, $value, $default = null)
    {
        $this->app['config']->set('defaults.' . $setting, $default);

        // mock only once
        if (empty($this->fake_settings)) {
            Setting::shouldReceive('get')
                ->andReturnUsing(function ($key) {
                    if (array_key_exists($key, $this->fake_settings)) {
                        return $this->fake_settings[$key];
                    }

                    return default_setting($key);
                });
        }

        // register the fake setting
        $this->fake_settings[$setting] = $value;
    }
}
