---
title: Settings pages
sidebar_position: 3
---

Every registered page gets a form, validation, saving and a page, in Blade or Inertia React. You only define the settings.

# Routes

```php
// routes/web.php
Route::settings();
```

| Route | Name | What |
| --- | --- | --- |
| `GET /settings` | `settings.index` | Redirects to the first page the user can see (JSON: the pages) |
| `GET /settings/{settings_page}` | `settings.edit` | The page's form (JSON: the page and its values) |
| `PATCH /settings/{settings_page}` | `settings.update` | Validates and saves, then redirects back with a "Settings saved." status (JSON: the message and the values) |

`Route::settings()` takes options:

| Option | Default | What |
| --- | --- | --- |
| `prefix` | `settings` | The URL prefix |
| `name` | `settings` | The route name prefix (`admin.settings` gives `admin.settings.edit`) |
| `guard` | `null` (the default guard) | The guard users are authenticated with; permissions are checked for its user |
| `middleware` | `['auth']`, or `["auth:{guard}"]` with a guard | The route middleware. Put the routes in your `web` group (or one of your own) for sessions |
| `controller` | `config('settings.controllers.settings')` | See [Overriding controllers and views](../advanced-usage/overriding-controllers-and-views.md) |

```php
Route::middleware('web')->group(function () {
    Route::settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
});
```

Link to a page with `route('settings.edit', 'general')`.

# What the page does

- Tabs link to every page the user may see (when there's more than one).
- The form has the page's settings, filled with their current values (old input after a failed save). File settings show the current file; encrypted settings are empty.
- Saving validates with the fields' rules, for the fields that are visible, and stores only the page's settings: other input is ignored.
- Users who can view but not edit see the form disabled, without a save button, and a notice.
- Requests that expect JSON get JSON, and `422` with the errors when validation fails.

[Live fields](https://github.com/Javaabu/stencil/blob/main/docs/basic-usage/live-fields.md) (`->live()`, closures with `$get`) work: add stencil's route once. Only users who may edit the page can re-evaluate its form.

```php
Route::stencil()->middleware(['web', 'auth']);
```

# Blade

The page is `settings::settings.edit`. It extends `config('settings.layout')` (`layouts.app`) and fills `config('settings.layout_section')` (`content`):

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'dv' ? 'rtl' : 'ltr' }}">
<head>
    @manifestStyles
</head>
<body>
    @yield('content')
    @manifestScripts
</body>
</html>
```

It gets these variables, if you [publish and change it](../advanced-usage/overriding-controllers-and-views.md):

| Variable | What |
| --- | --- |
| `$form` | The page's `SettingsForm` (echo it: `{{ $form }}`) |
| `$page` | `key`, `label`, `description`, `icon`, `url`, `can_update` |
| `$pages` | The pages the user can see, each as `$page` plus `active` |
| `$status` | The message after saving, or `null` |
| `$strings` | `title`, `read_only`, `pages`, translated |
| `$layout`, `$layout_section` | From the config |

# Inertia React

With `'views' => 'inertia'`, the page is `settings::Settings/Edit`, shipped through javaabu/manifest. It renders the form with stencil's `<StencilForm>`. The props are the variables above, with `form` as stencil's JSON schema.

```tsx
// resources/js/app.tsx: give package pages your layout
import { createInertiaApp } from "@inertiajs/react"
import { resolvePage } from "@javaabu/manifest/inertia"
import { createRoot } from "react-dom/client"
import AdminLayout from "@/layouts/admin"

createInertiaApp({
  resolve: async (name) => {
    const page = await resolvePage(name, import.meta.glob("./pages/**/*.tsx"))
    page.default.layout ??= (children: React.ReactNode) => <AdminLayout>{children}</AdminLayout>
    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

Override the page itself by copying it to `resources/vendor/settings/js/Pages/Settings/Edit.tsx` (manifest looks there first).

The components are also exported as `@javaabu/settings`, to build your own page:

```tsx
import { SettingsNav, type SettingsEditProps } from "@javaabu/settings"
import { StencilForm } from "@javaabu/stencil"

export default function Edit({ form, page, pages }: SettingsEditProps) {
  return (
    <AdminLayout title={page.label}>
      <SettingsNav pages={pages} />
      <StencilForm schema={form} />
    </AdminLayout>
  )
}
```

| Export | What |
| --- | --- |
| `SettingsEdit` | The page component |
| `SettingsNav` | Tabs linking to `pages` (nothing for a single page) |
| `SettingsEditProps`, `SettingsPageData`, `SettingsStrings` | The prop types |

# Your own forms

`SettingsForm` is a stencil form class, so you can use a page's form anywhere:

```php
use Javaabu\Settings\Forms\SettingsForm;

public function edit()
{
    return view('onboarding.settings', [
        'form' => SettingsForm::forPage('general')->action(route('onboarding.settings.update')),
    ]);
}

public function update(Request $request)
{
    $values = SettingsForm::forPage('general')->saveSettings($request, $request->user());

    return to_route('onboarding.done');
}
```

| Method | What |
| --- | --- |
| `SettingsForm::forPage($page, ?string $guard = null)` | The form of a page (a key or a `SettingsPage`), filled with the current values |
| `saveSettings(Request\|array\|null $input = null, ?Model $causer = null)` | Validates, uploads files, stores the page's settings and returns what was stored |
| `currentValues()` | The values the form starts with |
| `page()` | The `SettingsPage` |

Every other stencil `Form` method works too: `validate()`, `rules()`, `embedded()`, `render('json')`, `assertable()`.
