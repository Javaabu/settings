---
title: Installation & Setup
sidebar_position: 1.2
---

You can install the package via composer:

```bash
composer require javaabu/settings
```

# Setting up the defaults config file

You will need to setup a `config/defaults.php` config file with your default settings. You can do so by publishing the sample defaults config file that comes with this package.

```bash
php artisan vendor:publish --provider="Javaabu\Settings\SettingsServiceProvider" --tag="settings-defaults"
```

Below is the default content of the sample config file. You can replace the values in the file with the settings that you want.

```php
<?php

return [
    /**
     * Default Settings
     */

    'app_name' => env('APP_NAME', 'Laravel'),

    'favicon' => 'favicon.ico',
];

```



