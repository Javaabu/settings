---
title: Overriding controllers and views
sidebar_position: 5
---

# The controller

Extend `Javaabu\Settings\Http\Controllers\SettingsController` and point the config at it, or pass it to one set of routes:

```php
// config/settings.php
'controllers' => [
    'settings' => App\Http\Controllers\SettingsController::class,
],

// routes/web.php
Route::settings(['controller' => App\Http\Controllers\SettingsController::class]);
```

The methods to override:

| Method | What |
| --- | --- |
| `index(Request $request)` | Redirect to the first page (JSON: the pages) |
| `edit(Request $request, string $settings_page)` | Show a page |
| `update(Request $request, string $settings_page)` | Save a page |
| `updated(Request $request, SettingsPage $page, array $values)` | Called after a page is saved, with the values stored. Empty by default |
| `form(Request $request, SettingsPage $page)` | The page's `SettingsForm` |
| `viewData(Request $request, SettingsPage $page, SettingsForm $form)` | What the view or Inertia page gets |
| `navigation(Request $request, ?SettingsPage $current)` | The pages for the tabs |

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Http\Controllers\SettingsController as BaseController;

class SettingsController extends BaseController
{
    protected function updated(Request $request, SettingsPage $page, array $values): void
    {
        if (array_key_exists('site_name', $values)) {
            Artisan::call('sitemap:generate');
        }
    }
}
```

# The Blade view

```bash
php artisan vendor:publish --tag=settings-views
```

Edit `resources/views/vendor/settings/settings/edit.blade.php`. It gets the variables listed in [Settings pages](../basic-usage/settings-pages.md#blade). Or keep the package's view and only change the layout with `settings.layout` and `settings.layout_section`.

# The React page

Copy `vendor/javaabu/settings/resources/js/Pages/Settings/Edit.tsx` to `resources/vendor/settings/js/Pages/Settings/Edit.tsx` and change it: manifest's `resolvePage()` uses the app's copy first. Import the package's pieces as `@javaabu/settings` (`SettingsNav`, the prop types) and stencil's as `@javaabu/stencil`.

# The form fields

A setting's field is a stencil field, so its look comes from javaabu/forms (Blade) and @javaabu/forms (React). Use any stencil field, including your own (`php artisan make:stencil-field`), with `->field(MyField::class)`.
