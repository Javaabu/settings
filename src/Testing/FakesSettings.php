<?php

namespace Javaabu\Settings\Testing;

use anlutro\LaravelSettings\Facades\Setting;

trait FakesSettings
{
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
    protected function setFakeSetting(string $setting, $value, $default = null, bool $use_value_as_default_if_null = true)
    {
        if ($use_value_as_default_if_null && is_null($default)) {
            $default = $value;
        }

        $this->app['config']->set('defaults.' . $setting, $default);

        Setting::shouldReceive('get')
            ->with($setting, $default)
            ->andReturn($value);
    }
}
