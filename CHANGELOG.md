# Changelog

All notable changes will be documented in this file

## 2.0.0 - Unreleased

- Settings are defined with javaabu/stencil: `Setting::make()->default()->field()`, grouped into `SettingsPage`s (classes or inline) and `Section`s, or `Settings::define()` for settings without a page
- Own stores (database, json, array, custom drivers) replace anlutro/l4-settings; `settings:import-v1` copies v1 values
- `settings()` helper and `Settings` facade with typed getters, casts from the field, enums, encrypted settings and file URLs
- Values are cached under a version; queue workers (`Queue::before`) and Octane reload changed settings without a restart
- `Route::settings()`: a form, validation, saving and a page for every settings page, in Blade (javaabu/forms) or Inertia React (@javaabu/forms through javaabu/manifest), with JSON responses and live fields
- `SettingsPolicy` and `view_settings` / `edit_settings` (and per-page) permissions through javaabu/permissions; any guard and user model; overridable controller and policy
- Optional translatable settings through javaabu/translatable v3
- Optional activity log through spatie/laravel-activitylog (no longer a dependency); encrypted values aren't logged
- Dhivehi translations and RTL pages
- Docs for people and agents, `llms.txt`, Laravel Boost guideline and skill
- Requires PHP 8.4 and Laravel 12 or 13
- Kept from v1: `get_setting()`, `default_setting()`, `set_file_setting()`, `reset_file_setting()`, `config/defaults.php`, `FakesSettings`, `SettingsUpdated`. See `docs/upgrading.md`
