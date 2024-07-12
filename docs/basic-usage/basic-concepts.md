---
title: Basic Concepts
sidebar_position: 1
---

For each setting you use, you should have a default value in your `config/defaults.php` file with that setting key.

```php
<?php

return [
    /**
     * Default Settings
     */

    'app_name' => env('APP_NAME', 'Laravel'),

    'per_page' => 10,

    'logo' => 'img/logo.png',
    
    'featured_categories' => [
        'books',
        'fruits',
        'electronics'
    ]
];

```

## General usage

To use a setting value in your code, simply call `get_setting`.

```php
$app_name = get_setting('app_name');
```

To set a setting value, use the `Setting` facade's `set` method. You can see full instructions at [`aluntro/l4-settings`](https://github.com/anlutro/laravel-settings).

## File settings

For file settings, store the relative url of the file in your `defaults.php` config file. The url could be relative to your `public` folder and make sure you have the specified file in the specified path.

```php
// config/defaults.php
...
'logo' => 'img/logo.png',
...
```

When using the file setting, you can use Laravel's `asset()` helper method to get the full url of the file.


```php
$logo_url = asset(get_setting('logo'));
```

To set a file setting, use the `set_file_setting` helper method to set a file setting from the current request.

## Model based settings

For settings that refer to Models, you should use the slug or another uniquely identifiable value rather than the id of the Model as the id can potentially change when deployed to production.

```php
// config/defaults.php
...
'terms_page' => 'terms-and-conditions',
...
```

Tou use the model based setting, you can query the model.

```php
$terms_page = App\Models\Page::where('slug', get_setting('terms_page'))->first();

if ($terms_page) {
    // model exists
    // do stuff you want
}
```


