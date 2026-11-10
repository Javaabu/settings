---
title: Introduction
sidebar_position: 1.0
---

# Settings

[javaabu/settings](https://github.com/Javaabu/settings) keeps the settings of a Laravel app (the site name, items per page, the logo, an API key) that admins change without a deploy.

- **Defined once.** A setting's key, default, input and validation live together, defined with [javaabu/stencil](https://github.com/Javaabu/stencil) fields: `Setting::make('per_page')->default(20)->integer()->minValue(1)`.
- **Settings pages for free.** Group settings into pages and sections, add `Route::settings()`, and you get a form, validation, saving and a page in Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms)).
- **Typed.** Values come back as the field's type (integers, booleans, arrays, enums), with strict getters: `settings()->integer('per_page')`.
- **Fresh everywhere.** Values are cached, and queue workers and Octane reload them when they change, without a restart.
- **Permissions.** A policy and default permissions through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model, with a permission per page if you want one.
- **Dhivehi.** Every string is translated, the pages work right to left, and Thaana fields work out of the box. Translatable settings come with [javaabu/translatable](https://github.com/Javaabu/translatable).

```php
use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Stencil\Fields\Textarea;

class GeneralSettings extends SettingsPage
{
    public function definition(): array
    {
        return [
            Setting::make('site_name')->default(config('app.name'))->label(__('Site name'))->required()->thaana(),
            Setting::make('per_page')->default(20)->label(__('Items per page'))->integer()->minValue(1),
            Section::make(__('Search engines'))->schema([
                Setting::make('seo_description')->field(Textarea::class)->label(__('Description'))->thaana(),
            ]),
        ];
    }
}
```

```php
settings('site_name');                // 'ދިވެހިރާއްޖެ'
settings()->integer('per_page');      // 20
```

Start with [Installation & Setup](installation-and-setup.md), then [Defining settings](basic-usage/defining-settings.md). Coming from v1? Read [Upgrading](upgrading.md).
