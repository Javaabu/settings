---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- [javaabu/stencil](https://github.com/Javaabu/stencil) v1, [javaabu/permissions](https://github.com/Javaabu/permissions) v3, [javaabu/forms](https://github.com/Javaabu/forms) v2 and [javaabu/manifest](https://github.com/Javaabu/manifest) v1, installed with this package
- For the settings pages: Vite with manifest's plugin. The Inertia React page also needs `@javaabu/forms`, `@javaabu/onu`, React 19 and `@inertiajs/react` 2 or 3 in the app (`php artisan manifest:check` lists them).
- A cache store every process shares (Redis, Memcached, database or file) when you run queue workers or Octane, so they see changed settings. See [Queue workers and Octane](advanced-usage/queue-workers-and-octane.md).

Optional:

| Package | For |
| --- | --- |
| `inertiajs/inertia-laravel` ^2.0 | The Inertia React page (`settings.views` = `inertia`). See [Settings pages](basic-usage/settings-pages.md). |
| `javaabu/translatable` ^3.0 | A value per language for translatable settings. See [Translatable settings](advanced-usage/translatable-settings.md). |
| `spatie/laravel-activitylog` ^4.10 | A log of every change. See [Activity log](advanced-usage/activity-log.md). |
