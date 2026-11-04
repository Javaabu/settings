---
title: Upgrading from v1
sidebar_position: 1.25
---

v2 keeps its own store instead of [anlutro/l4-settings](https://github.com/anlutro/laravel-settings), defines settings with [javaabu/stencil](https://github.com/Javaabu/stencil), and adds settings pages. Code that reads settings with `get_setting()` keeps working.

# What still works

- `get_setting()`, `default_setting()`, `set_file_setting()`, `reset_file_setting()` and `remove_prefix()`.
- `config/defaults.php`: settings without a default in a definition fall back to it.
- `Javaabu\Settings\Testing\FakesSettings`: `setFakeSetting()` and `setFakeDefaultSetting()`.
- `Javaabu\Settings\Events\SettingsUpdated` with `$old_settings`, `$new_settings` and `$causer`, and the `settings` activity log entry.

# Steps

## 1. Update the requirements

PHP 8.4, Laravel 12 or 13. Then:

```bash
composer require javaabu/settings:^2.0
php artisan migrate
```

The migration creates the `settings` table. anlutro's `persistant_settings` table (or `storage/settings.json`) is left alone.

## Move the values

```bash
php artisan settings:import-v1                                   # from the persistant_settings table
php artisan settings:import-v1 --table=my_settings --connection=mysql
php artisan settings:import-v1 --from=json                      # from storage/settings.json
php artisan settings:import-v1 --from=json --path=/path/to/settings.json
```

Settings that already have a value in v2 are skipped unless you pass `--force`. Values are cast by their definitions when they have one (v1 stored everything as strings), so define your settings first, then import. Dotted keys (`featured.0`) are imported as arrays.

## 2. Replace the `Setting` facade

anlutro's facade is gone:

| v1 | v2 |
| --- | --- |
| `use anlutro\LaravelSettings\Facades\Setting;` | `use Javaabu\Settings\Facades\Settings;` |
| `Setting::get('key', $default)` | `Settings::get('key', $default)` or `settings('key', $default)` |
| `Setting::set('key', $value); Setting::save();` | `Settings::set('key', $value);` (saved straight away) |
| `Setting::has('key')` | `Settings::has('key')` |
| `Setting::forget('key'); Setting::save();` | `Settings::forget('key');` |
| `Setting::all()` | `Settings::all()` |
| `config('settings.store')` (anlutro's `json`/`database`) | `config('settings.store')` (`database`, `json`, `array`) in the new [config file](advanced-usage/configuration.md) |

anlutro's `config/settings.php` has the same name as v2's. Delete it (or publish v2's with `--tag=settings-config --force`).

## 3. Define your settings (optional, recommended)

Move each `config/defaults.php` entry that admins edit into a [definition](basic-usage/defining-settings.md), with its field and rules, and drop your hand-written settings controller and views for [`Route::settings()`](basic-usage/settings-pages.md):

```php
// v1: config/defaults.php
'per_page' => 20,

// v1: a controller validating and saving it
$request->validate(['per_page' => ['required', 'integer', 'min:1']]);
Setting::set('per_page', $request->input('per_page'));
Setting::save();

// v2: one definition
Setting::make('per_page')->default(20)->label(__('Items per page'))->required()->integer()->minValue(1);
```

## 4. Check what changed

- **Types.** Values keep their type (v1 returned strings from the database store). A defined setting is cast by its field: `per_page` is `20`, not `"20"`.
- **`SettingsUpdated::$causer`** is any `Illuminate\Database\Eloquent\Model` (v1 typed it as `App\Models\User`), and defaults to the authenticated user when you don't pass one.
- **Activity log** is optional: install `spatie/laravel-activitylog` yourself if you used it. It's no longer a dependency.
- **`FakesSettings`** no longer mocks a facade: it fakes values in memory with `Settings::fake()`. Tests that mocked `anlutro\LaravelSettings\Facades\Setting` directly need `Settings::fake([...])` instead.
- **`EventServiceProvider`** (`Javaabu\Settings\Providers\EventServiceProvider`) is gone; the listener is registered by the main provider.
- **Long-running workers** reload changed settings by themselves. Remove any `queue:restart` you ran after changing settings.
- **Caching.** Values are cached in the app's cache store. Use a shared store (Redis, database, file) when you run queue workers.
