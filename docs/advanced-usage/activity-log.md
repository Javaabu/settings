---
title: Activity log
sidebar_position: 6
---

With [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) installed, every change is logged:

```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

Each change is an activity with the description `settings`, caused by the user who made it, with the changed keys only:

```php
$activity->properties['attributes']; // ['site_name' => 'ދިވެހިރާއްޖެ']
$activity->properties['old'];        // ['site_name' => 'Laravel']
```

Encrypted settings are logged as `********`, before and after.

Without the package, nothing is logged. To turn logging off with it installed:

```php
// config/settings.php
'log_activity' => false,
```

To log differently, listen to `Javaabu\Settings\Events\SettingsUpdated` (see [Reading and writing](../basic-usage/reading-and-writing.md#the-settingsupdated-event)).
