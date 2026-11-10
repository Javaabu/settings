---
title: API reference
sidebar_position: 9
---

# Helpers

| Function | Returns |
| --- | --- |
| `settings()` | The `SettingsRepository` |
| `settings(string $key, mixed $default = null)` | A setting's value |
| `settings(array $values)` | Sets the values; returns the repository |
| `get_setting(string $key)` | A setting's value (v1) |
| `default_setting(string $key)` | A setting's default (v1) |
| `set_file_setting(string $key, string $path = 'storage/uploads')` | Saves the request's upload under `$key` into `public/{$path}` (v1) |
| `reset_file_setting(string $key)` | Deletes the upload from the public disk and resets the setting (v1) |
| `remove_prefix(string $needle, string $haystack)` | `$haystack` without `$needle` at its start (v1) |

# `Javaabu\Settings\Facades\Settings` / `SettingsRepository`

Reading:

| Method | Returns |
| --- | --- |
| `get(string $key, mixed $default = null)` | The value: stored, else the definition's default, else `config('defaults.{key}')`, else `$default` |
| `string()`, `integer()`, `float()`, `boolean()`, `array()` `(string $key, $default = null)` | The value, or a `SettingsException` when it's another type |
| `default(string $key)` | The default |
| `has(string $key)` | Whether a value is stored |
| `all()` | Every setting's value |
| `only(array $keys)` | Those settings' values |
| `stored()` | The stored values as the store keeps them (encrypted settings encrypted) |
| `url(string $key)` | The URL of a file setting |
| `translate(string $key, ?string $locale = null, bool $fallback = true)` | A translatable setting in a locale |
| `translations(string $key)` | A translatable setting's values, keyed by locale |

Writing:

| Method | What |
| --- | --- |
| `set(string\|array $key, mixed $value = null, ?Model $causer = null)` | Store values; fires `SettingsUpdated` when something changed |
| `forget(string\|array $keys, ?Model $causer = null)` | Remove stored values (back to defaults) |
| `flush()` | Remove every stored value |

Processes:

| Method | What |
| --- | --- |
| `refreshIfStale()` | Reload if another process changed settings; returns whether it did |
| `refresh()` | Forget the loaded values |
| `touch()` | Tell every process to reload |
| `version()` | The cache version the values were loaded at |

Definitions:

| Method | What |
| --- | --- |
| `page(SettingsPage\|string $page)` | Register a page class or instance, or get (or start) an inline page by key |
| `define(array\|Setting $settings)` | Define settings without a page |
| `pages()` | The visible pages, in order, keyed by key |
| `definition(string $key)` | A `Setting`, or `null` |
| `registry()` | The `SettingsRegistry` |

Stores and testing:

| Method | What |
| --- | --- |
| `store()` | The `SettingsStore` in use |
| `extend(string $driver, Closure $creator)` | Add a store driver |
| `fake(array $values = [])` | Keep values in memory from now on |
| `fakeDefault(string $key, mixed $value)` | Override a default |
| `isFaking()` | Whether values are faked |
| `stopFaking()` | Back to the store |
| `Settings::ignoreMigrations()` | Don't load the package's migration |

# `Javaabu\Settings\Definitions\Setting`

| Method | What |
| --- | --- |
| `Setting::make(string $key)` | A setting |
| `default(mixed $value)` | The default; a closure is called each time |
| `field(Field\|string\|Closure $field)` | The stencil field: a class, a field named after the key, or a closure given the key. Default `TextInput` |
| `cast(string\|Closure\|null $cast)` | `string`, `integer`, `float`, `boolean`, `array`, an enum class or a closure |
| `encrypted(bool $condition = true)` | Encrypt the stored value |
| `translatable(bool $condition = true)` | A value per language, with javaabu/translatable |
| any field method | Passed to the field (`label()`, `required()`, `options()`, …) |
| `getKey()`, `getDefault()`, `getField()`, `getCast()`, `isEncrypted()`, `isTranslatable()`, `isFile()` | |
| `castValue(mixed $value)`, `storableValue(mixed $value)` | Cast a value for reading, or for storing |

# `Javaabu\Settings\Definitions\Section`

| Method | What |
| --- | --- |
| `Section::make($heading = null, array\|Closure $schema = [])` | A section |
| `schema(array\|Closure $schema)` | Its settings and sections |
| any stencil section method | `description()`, `columns()`, … |

# `Javaabu\Settings\Definitions\SettingsPage`

Abstract. Implement `definition(): array`. Override `getKey()`, `getLabel()`, `getDescription()`, `getIcon()`, `getPermission()`, `getOrder()` and `isVisible()`, or set `$key`, `$permission` and `$order`. Also: `settings()` (keyed by key), `setting($key)`, `has($key)`, `components()` (the stencil components).

`InlinePage` (from `Settings::page('key')`) adds `schema()`, `label()`, `description()`, `icon()`, `permission()` and `order()`.

# `Javaabu\Settings\Forms\SettingsForm`

A `Javaabu\Stencil\Form`. `forPage(SettingsPage|string $page, ?string $guard = null)`, `saveSettings(Request|array|null $input = null, ?Model $causer = null)`, `currentValues()`, `page()`, `guard()`.

# Routes and HTTP

- `Route::settings(array $options = [])`: see [Settings pages](../basic-usage/settings-pages.md#routes).
- `Javaabu\Settings\Routing\SettingsRoutes::guard(Request $request)` and `namePrefix(Request $request)`.
- `Javaabu\Settings\Http\Controllers\SettingsController`: see [Overriding controllers and views](overriding-controllers-and-views.md).
- `Javaabu\Settings\Policies\SettingsPolicy`: `viewAny`, `view`, `update`; constants `VIEW` (`view_settings`) and `EDIT` (`edit_settings`).
- `Javaabu\Settings\Views\ViewRenderer`: renders `settings.edit` as Blade or `settings::Settings/Edit` with Inertia.

# Other classes

| Class | What |
| --- | --- |
| `Contracts\SettingsStore` | `all()`, `put(array)`, `forget(array)`, `flush()` |
| `Stores\DatabaseStore`, `Stores\JsonStore`, `Stores\ArrayStore`, `Stores\StoreManager` | The stores |
| `Events\SettingsUpdated` | `$old_settings`, `$new_settings`, `$causer`, `changes()`, `changed($key)` |
| `Listeners\LogSettingsUpdate` | Logs to spatie/laravel-activitylog |
| `Testing\FakesSettings` | `setFakeSetting()`, `setFakeDefaultSetting()`, `setFakeSettings()` |
| `Support\Translations` | The optional javaabu/translatable support |
| `Exceptions\SettingsException` | Wrong types, bad definitions, unknown pages |

# Commands

`php artisan settings:import-v1`: see [Upgrading](../upgrading.md#move-the-values).

# JavaScript (`@javaabu/settings`)

| Export | What |
| --- | --- |
| `SettingsEdit` | The `settings::Settings/Edit` page |
| `SettingsNav` | `{ pages, label?, className? }`: tabs to each page |
| `SettingsEditProps`, `SettingsPageData`, `SettingsStrings` | Types |
