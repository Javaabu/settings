---
title: Test
sidebar_position: 1
---

:::info

The `FakesSettings` trait is included by default in the [`javaabu/helpers`](https://github.com/Javaabu/helpers) `TestCase` class. So you don't need to separately include this trait if you are already extending that class for your tests. 

:::

This package provides a trait called `FakesSettings` for faking settings and default values. 

The trait provides the following methods:

- **`setFakeDefaultSetting(string $setting, $value)`**: Fake a setting default to a given value
- **`setFakeSetting(string $setting, $value, $default = null)`**: Fake a setting to a given value and optionally set a default to the given setting. If a default is not provided, it would be set to `null`. Note that when you need to set both a default and a setting to the same setting key, you should call this method with a default instead of calling the `setFakeDefaultSetting` method separately.

You can use the trait in your tests like so.

```php
...
use Javaabu\Settings\Testing\FakesSettings;

/** @test */
public function it_can_fake_a_default_setting()
{
    // faking a default setting
    
    $this->setFakeDefaultSetting('daily_limit', 20);

    $this->assertEquals(20, default_setting('daily_limit'));
}

/** @test */
public function it_can_fake_a_setting()
{
    // faking a setting value
    
    $this->setFakeSetting('app_name', 'fake name');

    $this->assertEquals('fake name', get_setting('app_name'));
}

/** @test */
public function it_can_fake_a_setting_with_a_default()
{
    // faking a setting value with a default
    
    $this->setFakeSetting('app_name', 'fake name', 'default name');

    $this->assertEquals('default name', default_setting('app_name'));
    $this->assertEquals('fake name', get_setting('app_name'));
}
...
```

