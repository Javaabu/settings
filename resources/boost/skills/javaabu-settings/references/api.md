# javaabu/settings API

## Setting

| Method | What |
| --- | --- |
| `Setting::make('key')` | A setting |
| `default($value)` | Default; a closure is called each time (`fn () => config('app.name')`) |
| `field(Field::class \| Field::make('key') \| fn (string $key) => Field)` | The stencil field; default `TextInput` |
| `cast('string' \| 'integer' \| 'float' \| 'boolean' \| 'array' \| Enum::class \| Closure)` | Override the cast from the field |
| `encrypted()` | Encrypted in the store, never sent to the browser, empty input keeps the value, hidden in the activity log |
| `translatable(layout: 'tabs', require_all: false)` | A value per language with javaabu/translatable (`{key}_{locale}` inputs) |
| any stencil field method | `label()`, `help()`, `placeholder()`, `required()`, `maxLength()`, `minValue()`, `options()`, `multiple()`, `email()`, `password()`, `thaana()`, `visible()`, `visibleWhen()`, `live()`, `rules()`, … |

## Section

`Section::make(__('Heading'))->description(__('...'))->columns(2)->schema([Setting..., Section...])`. Other methods go to stencil's Section.

## SettingsPage

```php
class MailSettings extends SettingsPage
{
    protected ?string $key = 'mail';                    // URL segment; default from the class name
    protected ?string $permission = 'edit_mail_settings'; // default edit_settings
    protected int $order = 10;

    public function getLabel(): string { return __('Mail'); }
    public function getDescription(): ?string { return __('Where emails come from'); }
    public function getIcon(): ?string { return 'envelope'; }
    public function isVisible(): bool { return true; }

    public function definition(): array { return [/* Setting and Section */]; }
}
```

Register: `config('settings.pages')`, or `Settings::page(MailSettings::class)` in `boot()`. Inline: `Settings::page('mail')->label(fn () => __('Mail'))->description(...)->icon(...)->permission(...)->order(...)->schema(fn () => [...])`.

## Repository (`settings()`, `Settings::`)

Read: `get($key, $default)`, `string|integer|float|boolean|array($key, $default)`, `default($key)`, `has($key)`, `all()`, `only([...])`, `url($key)`, `translate($key, $locale, $fallback)`, `translations($key)`, `definition($key)`, `pages()`.
Write: `set($key | [...], $value, causer: $user)`, `forget($keys, causer: $user)`, `flush()`.
Processes: `refreshIfStale()`, `refresh()`, `touch()`, `version()`.
Stores: `store()`, `extend('driver', fn ($app, array $config) => new MyStore)`.
Tests: `fake([...])`, `fakeDefault($key, $value)`, `isFaking()`, `stopFaking()`.

Value order: stored → definition default → `config('defaults.{key}')` → `$default`.

## Routes and the controller

`Route::settings(['prefix' => 'settings', 'name' => 'settings', 'guard' => null, 'middleware' => ['auth'], 'controller' => SettingsController::class])`:

| Route | Name |
| --- | --- |
| `GET {prefix}` | `{name}.index` (redirects to the first page) |
| `GET {prefix}/{settings_page}` | `{name}.edit` |
| `PATCH {prefix}/{settings_page}` | `{name}.update` |

JSON requests get JSON. Override `SettingsController` (config `settings.controllers.settings`); hooks: `updated($request, $page, $values)`, `form()`, `viewData()`, `navigation()`.

Page props (Blade variables and Inertia props of `settings::Settings/Edit`): `form` (the `SettingsForm`; JSON schema for Inertia), `page` (`key`, `label`, `description`, `icon`, `url`, `can_update`), `pages` (same, plus `active`), `status`, `strings` (`title`, `read_only`, `pages`).

`SettingsForm::forPage('general')` is a stencil form class: `->action(...)`, `->saveSettings($request, $user)`, `->currentValues()`, `->assertable()`.

## npm packages

Only the Inertia React page needs npm packages (`@javaabu/forms`, `@javaabu/onu`, React 19, `@inertiajs/react`). They're registered with manifest `for: 'pages'`, so `php artisan manifest:check` asks Blade-only apps for none of them; Inertia apps install what it lists.

## Permissions and policy

`view_settings` (see every page, read-only), `edit_settings` (edit pages without their own permission), and each page's `$permission`. `SettingsPolicy` on `SettingsPage`: `viewAny`, `view`, `update`. Override with `config('permissions.policies')`, `Gate::policy(SettingsPage::class, ...)` or `config('settings.policy')`.

## Config (`config/settings.php`)

`store` (database), `stores.database.{connection,table}`, `stores.json.path`, `cache.{enabled,store,prefix}`, `refresh.{queue,octane}`, `pages`, `disk` (public), `directory` (settings), `views` (blade|inertia), `layout`, `layout_section`, `controllers.settings`, `policy`, `register_permissions`, `log_activity`.

## Testing

```php
Settings::fake(['registration_open' => false]);

use Javaabu\Settings\Testing\FakesSettings;
$this->setFakeSetting('vat_rate', 8);
$this->setFakeDefaultSetting('per_page', 5);

$this->actingAs($admin)->patch(route('settings.update', 'general'), [...])->assertSessionHasNoErrors();

SettingsForm::forPage('general')->assertable()->assertFieldRequired('site_name');
```
