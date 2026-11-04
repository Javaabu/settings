# Settings

[![Tests](https://github.com/n4ff4h/settings/actions/workflows/tests.yml/badge.svg?branch=next-gen)](https://github.com/n4ff4h/settings/actions/workflows/tests.yml?query=branch%3Anext-gen)
[![codecov](https://codecov.io/gh/n4ff4h/settings/branch/next-gen/graph/badge.svg)](https://codecov.io/gh/n4ff4h/settings/tree/next-gen)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/settings.svg)](https://packagist.org/packages/javaabu/settings)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Settings for Laravel, defined once with [javaabu/stencil](https://github.com/Javaabu/stencil): the field, its default and its validation in one place.

- Settings pages for free: `Route::settings()` gives every page a form, validation, saving and a page in Blade or Inertia React.
- Typed values (integers, booleans, arrays, enums), encrypted secrets and file uploads.
- Cached, and picked up by queue workers and Octane without a restart.
- A policy and permissions through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model.
- Dhivehi and RTL; translatable settings with [javaabu/translatable](https://github.com/Javaabu/translatable).

```php
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;

class GeneralSettings extends SettingsPage
{
    public function definition(): array
    {
        return [
            Setting::make('site_name')->default(config('app.name'))->label(__('Site name'))->required()->maxLength(255)->thaana(),
            Setting::make('per_page')->default(20)->label(__('Items per page'))->integer()->minValue(1),
        ];
    }
}
```

```php
Route::settings();                       // /settings/general, for users with edit_settings

settings('site_name');
settings()->integer('per_page');
Settings::set(['site_name' => 'ދިވެހިރާއްޖެ']);
```

Coming from v1? `get_setting()` still works; see [Upgrading](docs/upgrading.md).

## Documentation

You'll find the documentation on [https://docs.javaabu.com/settings](https://docs.javaabu.com/settings), and in [`docs/`](docs/intro.md).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving this package? Feel free to create an [issue](../../issues) on GitHub, we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [Arushad Ahmed (@dash8x)](http://arushad.com)
- [Athfan Khaleel (@athphane)](https://github.com/athphane)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
