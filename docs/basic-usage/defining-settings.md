---
title: Defining settings
sidebar_position: 1
---

A setting has a key, a default, and a [stencil](https://github.com/Javaabu/stencil) field that gives its input, its label and its validation. You define all three in one place, and the package uses them everywhere: to read the value, to build the form, to validate and to cast.

```php
use Javaabu\Settings\Definitions\Setting;

Setting::make('site_name')
    ->default(config('app.name'))
    ->label(__('Site name'))
    ->required()
    ->maxLength(255)
    ->thaana();
```

# The field

Without `field()`, a setting is a `TextInput`. Give it another stencil field by class; the field is named after the setting:

```php
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\ImageUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;

Setting::make('registration_open')->default(true)->field(Checkbox::class)->label(__('Registration is open'));

Setting::make('theme')->default('light')->field(Select::class)->options([
    'light' => __('Light'),
    'dark' => __('Dark'),
])->required();

Setting::make('seo_description')->field(Textarea::class)->label(__('Description'))->thaana();

Setting::make('featured_categories')->default(['books'])->field(CheckboxList::class)->options($categories);

Setting::make('logo')->default('img/logo.png')->field(ImageUpload::class)->label(__('Logo'));
```

Any method the setting doesn't have is passed to the field, in order, whenever a field is made: `label()`, `help()`, `required()`, `maxLength()`, `options()`, `integer()`, `email()`, `thaana()`, `visible()`, `live()`, `rules()`… See stencil's [Fields](https://github.com/Javaabu/stencil/blob/main/docs/basic-usage/fields.md) for all of them. You can call them before or after `field()`.

`field()` also takes a field named after the setting, or a closure given the key:

```php
Setting::make('per_page')->field(TextInput::make('per_page')->integer());
Setting::make('per_page')->field(fn (string $key) => TextInput::make($key)->integer());
```

A field with another name throws a `SettingsException`.

# Defaults

`default()` is the value when nothing is stored. A closure is called each time the default is needed, so it can read config that changes:

```php
Setting::make('app_title')->default(fn () => config('app.name'));
```

When a setting has no default (or it's `null`), `config('defaults.{key}')` is used, as in v1, and then the default you pass to `get()`.

# Casts

Values come back as the field's type:

| Field | Value |
| --- | --- |
| `Checkbox` | `bool` |
| `TextInput` with `integer()` | `int` |
| `TextInput` with `numeric()` | `float` |
| `CheckboxList`, `Select` with `multiple()`, `FileUpload` with `multiple()` | `array` |
| anything else | as stored (a string for text inputs) |

Set it yourself with `cast()`: `string`, `integer`, `float`, `boolean`, `array`, a backed enum class, or a closure.

```php
use App\Enums\Themes;

Setting::make('theme')->default(Themes::LIGHT)->cast(Themes::class)->field(Select::class)->options(Themes::class);

settings('theme'); // Themes::LIGHT
```

Enums are stored as their values.

# Encrypted settings

API keys and passwords:

```php
Setting::make('sms_api_key')->encrypted()->password()->label(__('API key'));
```

The value is encrypted with the app key in the store, and decrypted when you read it. On the settings page the field is always empty (the value never reaches the browser), with the help text "Leave blank to keep the current value."; leaving it empty keeps the value. The [activity log](../advanced-usage/activity-log.md) records that it changed, not the value.

:::warning

Don't add `required()` to an encrypted setting: its input is empty every time the page opens.

:::

# Pages

Settings are grouped into pages. Each page has its own URL, form and permission. Extend `SettingsPage` and return the settings from `definition()`:

```php
<?php

namespace App\Settings;

use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Stencil\Fields\Textarea;

class GeneralSettings extends SettingsPage
{
    public function getLabel(): string
    {
        return __('General');
    }

    public function getDescription(): ?string
    {
        return __('The name and look of the site');
    }

    public function definition(): array
    {
        return [
            Setting::make('site_name')->default(config('app.name'))->label(__('Site name'))->required()->thaana(),

            Section::make(__('Search engines'))
                ->description(__('How the site appears in search results'))
                ->columns(2)
                ->schema([
                    Setting::make('seo_description')->field(Textarea::class)->label(__('Description'))->thaana(),
                    Setting::make('seo_keywords')->label(__('Keywords')),
                ]),
        ];
    }
}
```

| Property or method | Default | What |
| --- | --- | --- |
| `protected ?string $key` / `getKey()` | the class name without `Settings`, in kebab case (`general`) | The page's URL segment |
| `getLabel()` | the key as a headline | The tab label |
| `getDescription()` | `null` | Shown under the heading |
| `getIcon()` | `null` | An icon name for the tab |
| `protected ?string $permission` / `getPermission()` | `null`: `edit_settings` | The permission that edits the page. See [Permissions](../advanced-usage/permissions-and-policies.md) |
| `protected int $order` / `getOrder()` | `0` | Pages are listed by order, then as registered |
| `isVisible()` | `true` | Return `false` to hide the page (it returns 404) |

Register pages in `config/settings.php` (`'pages' => [GeneralSettings::class]`) or with `Settings::page(GeneralSettings::class)` in a service provider's `boot()`.

## Sections

`Section::make($heading)` groups settings on a page, rendered as a stencil section (a card). It takes `description()`, and passes anything else (`columns()`, `columnSpanFull()`, …) to the stencil section. Sections can be nested.

A page's schema holds `Setting`s and `Section`s only. Anything else throws a `SettingsException`.

## Inline pages

For a page or two, register it from a service provider without a class. Pass closures so strings are translated in each request's locale:

```php
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Facades\Settings;

public function boot(): void
{
    Settings::page('mail')
        ->label(fn () => __('Mail'))
        ->description(fn () => __('Where emails come from'))
        ->icon('envelope')
        ->permission('edit_mail_settings')
        ->order(10)
        ->schema(fn () => [
            Setting::make('mail_from_address')->default('info@example.com')->label(__('From address'))->email()->required(),
            Setting::make('mail_from_name')->default(config('app.name'))->label(__('From name'))->thaana(),
        ]);
}
```

`Settings::page('mail')` returns the same page when called again.

# Settings without a page

Settings that admins don't edit can still have a default, a cast and typed access:

```php
Settings::define([
    Setting::make('skeleton_version')->default('9.0.0'),
    Setting::make('max_upload_size')->default(2048)->cast('integer'),
]);
```

# Rules

- A key is defined once: the same key on two pages throws a `SettingsException` when settings are first read.
- Use plain keys (`seo_description`), not dots.
- Every label, help text and option goes through `__()`. Add `->thaana()` to text settings that take Dhivehi.
- Put validation on the field (`required()`, `maxLength()`, `rules([...])`), never in a controller.
