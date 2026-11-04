# Settings

[![Tests](https://github.com/n4ff4h/settings/actions/workflows/tests.yml/badge.svg?branch=next-gen)](https://github.com/n4ff4h/settings/actions/workflows/tests.yml?query=branch%3Anext-gen)
[![codecov](https://codecov.io/gh/n4ff4h/settings/branch/next-gen/graph/badge.svg)](https://codecov.io/gh/n4ff4h/settings/tree/next-gen)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/settings.svg)](https://packagist.org/packages/javaabu/settings)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Settings for Laravel, defined once with [javaabu/stencil](https://github.com/Javaabu/stencil): the field, its default and its validation in one place.

```php
use Javaabu\Settings\Definitions\Setting;

Setting::make('site_name')->default(config('app.name'))->required()->maxLength(255);
```

```php
settings('site_name');
settings()->integer('per_page');
settings(['site_name' => 'ދިވެހިރާއްޖެ']);
```

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
