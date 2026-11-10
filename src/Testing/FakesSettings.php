<?php

namespace Javaabu\Settings\Testing;

use Javaabu\Settings\Facades\Settings;

/**
 * Fake settings and defaults in tests. Values are kept in memory, so nothing is
 * written to the store or the cache.
 */
trait FakesSettings
{
    /**
     * Fake the default value of a setting
     */
    protected function setFakeDefaultSetting(string $setting, mixed $value): void
    {
        $this->app['config']->set("defaults.{$setting}", $value);

        Settings::fakeDefault($setting, $value);
    }

    /**
     * Fake the value of a setting, and its default ($default, null when not given)
     */
    protected function setFakeSetting(string $setting, mixed $value, mixed $default = null): void
    {
        $this->setFakeDefaultSetting($setting, $default);

        Settings::fake([$setting => $value]);
    }

    /**
     * Fake several settings
     *
     * @param  array<string, mixed>  $settings
     */
    protected function setFakeSettings(array $settings): void
    {
        Settings::fake($settings);
    }
}
