---
title: Translatable settings
sidebar_position: 7
---

With [javaabu/translatable](https://github.com/Javaabu/translatable) v3 installed, a setting can have a value per language: the site name in English and in Dhivehi.

```bash
composer require javaabu/translatable:^3.0@dev
```

```php
Setting::make('site_title')->default('Maldives')->label(__('Site title'))->required()->maxLength(100)->translatable();
Setting::make('footer_text')->field(Textarea::class)->label(__('Footer'))->translatable();
```

`->translatable()` is always safe. When translatable is installed and enabled (`translatable.enabled`, with active languages), the setting is translatable; otherwise it's a plain setting.

# The settings page

A translatable setting has one input per active language, named `{key}_{locale}` (`site_title_en`, `site_title_dv`), in tabs, through stencil's [translatable fields](https://github.com/Javaabu/stencil/blob/main/docs/advanced-usage/translatable.md). Each input has its language's direction and `lang`, and Dhivehi gets the Thaana keyboard. Blade and React render the same inputs.

The default language keeps the setting's rules; the others are optional (`required` becomes `nullable`). To require every language, or to stack the inputs instead of tabs:

```php
Setting::make('site_title')->required()->translatable(require_all: true);
Setting::make('footer_text')->field(Textarea::class)->translatable(layout: 'stacked');
```

# Reading

```php
settings('site_title');                       // in the current language, falling back to the default language
Settings::translate('site_title', 'dv');      // in Dhivehi, falling back
Settings::translate('site_title', 'dv', fallback: false); // in Dhivehi, or null
Settings::translations('site_title');         // ['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ']
```

The current language is translatable's (`Translatable::currentLocale()`), so its locale middleware and language switcher apply.

# Writing

A translatable setting is stored as a map of locale to value:

```php
Settings::set('site_title', ['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ']);
```

The settings page merges what's submitted into what's stored, so a language that isn't active keeps its value.

# Without translatable

- Before you install it, or with `translatable.enabled` off, a translatable setting is a plain setting: one input, a plain value.
- Values stored as a map are still read in the current app locale, falling back to `app.fallback_locale`, then to the first value that isn't empty. So turning translations off doesn't break reading.
- Saving the page with translations off stores a plain value again.

# How it works

When the page has a translatable setting, its form edits a `Javaabu\Settings\Support\Translatable\SettingsRecord` instead of an array: a JSON-translatable model, never saved, with the default language's values as attributes and the others in `translations`. That is what stencil and translatable expect, so the fields, rules and old input work as for any translatable model. The record class only loads when translatable is installed.

`Javaabu\Settings\Support\Translations` is the one place the package checks for translatable. Set `Translations::$enabled = false` to turn translatable settings off while keeping the package installed for your models.
