---
title: Installation & Setup
sidebar_position: 1.2
---

```bash
composer require javaabu/settings
php artisan migrate
```

The migration creates the `settings` table (a row per setting, the value as JSON). Nothing else is needed: settings are read from the database and cached with the app's cache store.

# Define your settings

Put each page of settings in a class:

```php
<?php

namespace App\Settings;

use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;

class GeneralSettings extends SettingsPage
{
    public function getLabel(): string
    {
        return __('General');
    }

    public function definition(): array
    {
        return [
            Setting::make('site_name')->default(config('app.name'))->label(__('Site name'))->required()->maxLength(255)->thaana(),
            Setting::make('per_page')->default(20)->label(__('Items per page'))->integer()->minValue(1)->maxValue(100),
        ];
    }
}
```

and register it in `config/settings.php`, or from a service provider:

```php
// config/settings.php
'pages' => [
    App\Settings\GeneralSettings::class,
],
```

```php
// AppServiceProvider::boot()
use Javaabu\Settings\Facades\Settings;

Settings::page(GeneralSettings::class);
```

See [Defining settings](basic-usage/defining-settings.md) for every option.

# Add the settings pages

Register the routes where you want them, with your middleware:

```php
// routes/web.php
Route::settings();                                    // /settings, auth middleware, settings.* names

// or, for an admin portal on its own guard
Route::middleware(['web'])->group(function () {
    Route::settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
});
```

Sync the permissions and give them to a role:

```bash
php artisan permissions:sync
```

Users with `edit_settings` can now open `/settings`. See [Settings pages](basic-usage/settings-pages.md).

## Blade

The Blade page extends `layouts.app` and fills its `content` section. Change both in the config:

```php
'layout' => 'layouts.admin',
'layout_section' => 'main',
```

The layout needs manifest's tags (`@manifestStyles` and `@manifestScripts`) or its automatic injection, as for any javaabu/forms page.

## Inertia React

```bash
composer require inertiajs/inertia-laravel
```

```php
// config/settings.php, or SETTINGS_VIEWS=inertia
'views' => 'inertia',
```

The page is `settings::Settings/Edit`, resolved by manifest's Vite plugin. Install the npm packages the page needs (`php artisan manifest:check` lists them), and wrap it in your layout where you resolve pages.

# Publishing

| Tag | What |
| --- | --- |
| `settings-config` | `config/settings.php` |
| `settings-migrations` | The migration. Call `Settings::ignoreMigrations()` in a service provider's `register()` after publishing it |
| `settings-views` | The Blade view, to `resources/views/vendor/settings` |
| `settings-translations` | `lang/vendor/settings` |
| `settings-defaults` | A sample `config/defaults.php`, the v1 way of setting defaults |

```bash
php artisan vendor:publish --tag=settings-config
```
