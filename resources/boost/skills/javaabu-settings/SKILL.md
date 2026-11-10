---
name: javaabu-settings
description: Define, read, write and edit Laravel app settings with javaabu/settings. Use whenever the project depends on javaabu/settings and the task adds or changes a setting (site name, per page, logo, API keys, mail sender), reads one in code or Blade (settings(), get_setting(), Settings::get()), saves settings, builds or changes a settings page or its sections (SettingsPage, Section, Route::settings()), uploads a file setting, stores a secret, makes a setting translatable, changes who may edit settings, deals with queue workers seeing old settings, upgrades from settings v1, or tests code that reads settings.
license: MIT
metadata:
  author: Javaabu
---

# Settings with javaabu/settings

A setting is defined once, with its default, its [javaabu/stencil](https://github.com/Javaabu/stencil) field and its validation. The package reads, casts, caches and saves it, and builds the settings page. The docs ship with the package: `vendor/javaabu/settings/llms.txt` lists every page in `vendor/javaabu/settings/docs/`.

| Task | Read |
| --- | --- |
| Define, read, write and show settings | This file |
| Every option, the page props, overriding, permissions | [references/api.md](references/api.md) |
| Mistakes to avoid, upgrading from v1 | [references/common-mistakes.md](references/common-mistakes.md) |

## A settings page

```php
<?php

namespace App\Settings;

use App\Enums\Themes;
use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\ImageUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;

class GeneralSettings extends SettingsPage
{
    public function getLabel(): string
    {
        return __('General');
    }

    public function definition(): array
    {
        return [
            Setting::make('site_name')
                ->default(config('app.name'))
                ->label(__('Site name'))
                ->required()
                ->maxLength(255)
                ->thaana(),
            Setting::make('per_page')
                ->default(20)
                ->label(__('Items per page'))
                ->integer()
                ->minValue(1)
                ->maxValue(100),
            Setting::make('registration_open')
                ->default(true)
                ->field(Checkbox::class)
                ->label(__('Registration is open')),
            Setting::make('theme')
                ->default(Themes::LIGHT)
                ->cast(Themes::class)
                ->field(Select::class)
                ->options(Themes::class)
                ->required(),
            Setting::make('logo')
                ->default('img/logo.png')
                ->field(ImageUpload::class)
                ->label(__('Logo')),
            Section::make(__('Search engines'))->columns(2)->schema([
                Setting::make('seo_description')->field(Textarea::class)->label(__('Description'))->thaana(),
            ]),
        ];
    }
}
```

```php
// config/settings.php
'pages' => [App\Settings\GeneralSettings::class],

// routes/web.php, inside the app's web (or admin) group
Route::settings();                                                                    // /settings
Route::settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
```

```bash
php artisan permissions:sync   # view_settings, edit_settings
```

That's the whole page: tabs per page, the form, validation from the fields, saving, uploads, a success message, in Blade or Inertia React (`settings.views`).

## Reading and writing

```php
settings('site_name');                     // the value, or its default
settings()->integer('per_page');           // typed: throws on the wrong type
settings()->boolean('registration_open');
settings('theme');                         // Themes::LIGHT (cast)
settings()->url('logo');                   // the file's URL

Settings::set(['site_name' => 'ދިވެހިރާއްޖެ', 'per_page' => 25]);   // saved and broadcast to workers
Settings::forget('site_name');                                      // back to the default
```

```blade
<title>{{ settings('site_name') }}</title>
<img src="{{ settings()->url('logo') }}" alt="">
```

## Rules

- One `Setting::make()` per key, with its `default()`, field and rules. Calls the setting doesn't know go to the field (`label()`, `required()`, `integer()`, `options()`, `email()`, `thaana()`, `rules([...])`). Default field: `TextInput`.
- Pick the field for the type: `Checkbox` → bool, `->integer()` → int, `->numeric()` → float, `CheckboxList`/`->multiple()` → array, `->cast(Enum::class)` → enum.
- Secrets get `->encrypted()` (and usually `->password()`), never `->required()`.
- Pages: a `SettingsPage` class per page; `protected ?string $permission = 'edit_mail_settings'` for a page some users may edit alone; `Settings::page('mail')->label(fn () => __('Mail'))->schema(fn () => [...])` for an inline page (closures, so strings translate per request).
- Settings no one edits: `Settings::define([Setting::make('skeleton_version')->default('9.0.0')])`.
- Every label, help text, heading and option goes through `__()`; add `->thaana()` to Dhivehi text settings; `->translatable()` for a value per language (javaabu/translatable).
- Workers and Octane reload changed settings themselves, as long as the cache store is shared (Redis, database, file).
- Tests: `Settings::fake([...])`, or `FakesSettings`' `setFakeSetting('key', $value)`.

## Common mistakes

- Validating settings in a controller, or writing your own settings controller and form: define the field and rules on the `Setting` and use `Route::settings()`.
- `config('defaults.per_page')` or a new key in `config/defaults.php`: define the setting with `->default()`.
- `(int) settings('per_page')`: define it with `->integer()` and use `settings()->integer('per_page')`.
- `DB::table('settings')` or `Cache::forget(...)`: use `Settings::set()` / `forget()`; direct writes don't reach workers.
- `use anlutro\LaravelSettings\Facades\Setting; Setting::set(...); Setting::save();`: v1. Use `Settings::set()`.
- `Setting::make('site_name')->field(TextInput::make('name'))`: the field must be named after the setting; pass the class instead.
- Forgetting `Route::settings()` or `permissions:sync`: the pages 404 or 403.
- `php artisan queue:restart` after changing settings: not needed.
