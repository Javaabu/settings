# Common mistakes and upgrading from v1

## Mistakes

| Wrong | Right |
| --- | --- |
| A settings controller with `$request->validate([...])` and `Setting::set()` | Rules on the `Setting` (`->required()->integer()`), `Route::settings()` |
| `'per_page' => 20` in `config/defaults.php` | `Setting::make('per_page')->default(20)->integer()` |
| `(int) get_setting('per_page')` | `settings()->integer('per_page')` |
| `Setting::make('logo')` with `->field(FileUpload::class)` then `asset(settings('logo'))` | `settings()->url('logo')` (handles uploads on the disk and default paths) |
| Storing an API key as a plain setting | `->encrypted()->password()` |
| `->encrypted()->required()` | No `required()`: the input is empty on every visit |
| `Setting::make('mail.from')` | Plain keys: `mail_from_address` |
| The same key on two pages | One definition per key (it throws) |
| `Settings::page('mail')->label(__('Mail'))` in a provider | `->label(fn () => __('Mail'))`: translated per request |
| `DB::table('settings')->update(...)` | `Settings::set(...)`: bumps the version so workers reload |
| `php artisan queue:restart` after saving settings | Not needed; use a shared cache store |
| `Setting::shouldReceive('get')` in tests | `Settings::fake([...])` or `setFakeSetting()` |
| `Gate::define('edit-settings', ...)` | `edit_settings` permission, or override `SettingsPolicy` |
| Editing the package's Blade view in `vendor/` | `php artisan vendor:publish --tag=settings-views`, or `settings.layout` |

## Upgrading from v1

- `composer require javaabu/settings:^2.0`, `php artisan migrate`, then `php artisan settings:import-v1` (`--from=json` for `storage/settings.json`; `--force` to overwrite). Define settings first so imported strings are cast.
- Replace `anlutro\LaravelSettings\Facades\Setting` with `Javaabu\Settings\Facades\Settings`; drop `Setting::save()` (writes save immediately).
- Delete anlutro's `config/settings.php` (v2 has its own with the same name).
- `get_setting()`, `default_setting()`, `set_file_setting()`, `reset_file_setting()`, `config/defaults.php`, `FakesSettings` and `SettingsUpdated` keep working.
- Install `spatie/laravel-activitylog` yourself if you used the settings log.
