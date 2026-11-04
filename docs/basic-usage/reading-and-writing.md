---
title: Reading and writing settings
sidebar_position: 2
---

# Reading

```php
settings('site_name');                 // the value, or its default
settings('per_page', 15);              // with a fallback for a setting that has no default
Settings::get('site_name');            // the same, through the facade
get_setting('site_name');              // the v1 helper, still there
```

A setting's value is, in order:

1. its stored value (even `null`, if that's what was saved),
2. its definition's `default()`,
3. `config('defaults.{key}')`,
4. the default you pass.

Values are [cast](defining-settings.md#casts) on the way out.

## Typed getters

When you need a type, ask for it. They throw a `SettingsException` when the value is another type, so a misconfigured setting fails loudly instead of misbehaving:

```php
settings()->string('site_name');
settings()->integer('per_page');
settings()->float('vat_rate');            // integers are returned as floats
settings()->boolean('registration_open');
settings()->array('featured_categories');

settings()->integer('per_page', 20);      // with a fallback
```

## Everything else

| Method | Returns |
| --- | --- |
| `Settings::all()` | Every setting: the defined ones, the stored ones and the ones in `config('defaults')`, keyed by key |
| `Settings::only(['site_name', 'per_page'])` | Those settings |
| `Settings::has('site_name')` | Whether it has a stored value |
| `Settings::default('site_name')` / `default_setting('site_name')` | Its default |
| `Settings::url('logo')` | The URL of a file setting (see below) |
| `Settings::translate('site_name', 'dv')` | A [translatable setting](../advanced-usage/translatable-settings.md) in a locale |
| `Settings::definition('site_name')` | Its `Setting` definition, or `null` |
| `Settings::pages()` | The registered pages, in order |

In Blade:

```blade
<title>{{ settings('site_name') }}</title>
<img src="{{ settings()->url('logo') }}" alt="{{ settings('site_name') }}">
```

# Writing

```php
Settings::set('site_name', 'ދިވެހިރާއްޖެ');
Settings::set(['site_name' => 'ދިވެހިރާއްޖެ', 'per_page' => 25]);
settings(['site_name' => 'ދިވެހިރާއްޖެ']);

Settings::forget('site_name');          // back to the default
Settings::forget(['site_name', 'per_page']);
Settings::flush();                      // remove every stored value
```

Writes go straight to the store, then:

- the cached values are dropped and a new version is put in the cache, so other processes and [queue workers](../advanced-usage/queue-workers-and-octane.md) reload them;
- `Javaabu\Settings\Events\SettingsUpdated` is fired, if a value changed.

`set()` and `forget()` take the user who made the change as `causer` (the authenticated user by default), for the [activity log](../advanced-usage/activity-log.md):

```php
Settings::set($values, causer: $request->user('web_admin'));
```

Enums are stored as their values and encrypted settings are encrypted. Values must be JSON-serializable.

## The SettingsUpdated event

```php
use Javaabu\Settings\Events\SettingsUpdated;

Event::listen(function (SettingsUpdated $event) {
    if ($event->changed('maintenance_message')) {
        Cache::forget('banner');
    }
});
```

| Property or method | What |
| --- | --- |
| `$event->old_settings` | The values before, keyed by key |
| `$event->new_settings` | The values after |
| `$event->causer` | The user model, or `null` |
| `$event->changes()` | Only the keys that changed, with their new values |
| `$event->changed('key')` | Whether that key changed |

# File settings

A setting with a `FileUpload` or `ImageUpload` field stores a path. The settings page uploads files to `config('settings.disk')` (`public` by default) in `config('settings.directory')` (`settings`), and deletes the old file when a new one replaces it.

`Settings::url('logo')` gives the URL: the disk's URL for an upload, `asset()` for a default path such as `img/logo.png` (a file in `public/`), and the value itself for a full URL.

The v1 helpers are still there: `set_file_setting('logo')` saves the file uploaded under that name in the current request (into `public/storage/uploads`), and `reset_file_setting('logo')` deletes it from the public disk and resets the setting.

# Model settings

For settings that point at a model, store something that doesn't change between environments, such as a slug, rather than an id:

```php
Setting::make('terms_page')->default('terms-and-conditions')->field(Select::class)->options(fn () => Page::pluck('title', 'slug'));

$terms = Page::where('slug', settings('terms_page'))->first();
```
