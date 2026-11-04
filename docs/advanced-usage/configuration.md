---
title: Configuration
sidebar_position: 1
---

```bash
php artisan vendor:publish --tag=settings-config
```

| Key | Env | Default | What |
| --- | --- | --- | --- |
| `store` | `SETTINGS_STORE` | `database` | Where values are kept: `database`, `json`, `array`, or a driver added with `Settings::extend()`. See [Stores and caching](stores-and-caching.md) |
| `stores.database.connection` | `SETTINGS_DB_CONNECTION` | `null` (the default connection) | The connection of the settings table; the migration uses it too |
| `stores.database.table` | | `settings` | The table; set it before migrating |
| `stores.json.path` | | `storage_path('app/settings.json')` | The JSON file |
| `cache.enabled` | `SETTINGS_CACHE` | `true` | Cache the values. Off: the store is read once per request (or job) |
| `cache.store` | `SETTINGS_CACHE_STORE` | `null` (the default cache store) | The cache store; use one every process shares |
| `cache.prefix` | | `javaabu_settings` | The prefix of the cache keys |
| `refresh.queue` | `SETTINGS_REFRESH_QUEUE` | `true` | Workers reload changed settings before each job. See [Queue workers and Octane](queue-workers-and-octane.md) |
| `refresh.octane` | `SETTINGS_REFRESH_OCTANE` | `true` | Octane reloads changed settings before each request, task and tick |
| `pages` | | `[]` | `SettingsPage` classes to register |
| `disk` | `SETTINGS_DISK` | `public` | Where file settings are uploaded (`null`: the default disk) |
| `directory` | | `settings` | The folder on the disk |
| `views` | `SETTINGS_VIEWS` | `blade` | The pages' stack: `blade` or `inertia` |
| `layout` | | `layouts.app` | The Blade layout the page extends |
| `layout_section` | | `content` | The section it fills |
| `controllers.settings` | | `SettingsController::class` | The controller `Route::settings()` uses |
| `policy` | | `SettingsPolicy::class` | The default policy of settings pages |
| `register_permissions` | | `true` | Register `view_settings`, `edit_settings` and the pages' permissions with javaabu/permissions |
| `log_activity` | | `true` | Log changes when spatie/laravel-activitylog is installed |

`config/defaults.php` (publish it with `--tag=settings-defaults`) still works as in v1: a `key => default` array for settings without a default in their definition.
