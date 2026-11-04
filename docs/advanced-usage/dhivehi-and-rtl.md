---
title: Dhivehi and RTL
sidebar_position: 8
---

# Strings

Every string the package shows is translated into Dhivehi, in `lang/dv.json` (JSON translations, keyed by the English string). Publish them to change them:

```bash
php artisan vendor:publish --tag=settings-translations
```

Your settings' labels, help texts, section headings, page labels and options are yours: pass them through `__()` and translate them in your app.

# Right to left

The Blade and React pages use logical layout only (no left or right), so they work in RTL: set `dir="rtl"` on the `<html>` of your layout for Dhivehi. The fields come from javaabu/forms and @javaabu/forms, which support RTL.

# Thaana

Add `->thaana()` to text settings that take Dhivehi. The input gets `dir="rtl"`, `lang="dv"` and the Thaana keyboard:

```php
Setting::make('site_name')->label(__('Site name'))->thaana();
Setting::make('seo_description')->field(Textarea::class)->thaana();
```

For a value per language, see [Translatable settings](translatable-settings.md).

# Translations to review

These were translated by the package's authors without a native speaker's review. Corrections are welcome.

| English | Dhivehi |
| --- | --- |
| Settings | ސެޓިންގްސް |
| Settings pages | ސެޓިންގްސް ސަފްޙާތައް |
| Save settings | ސެޓިންގްސް ސޭވްކުރޭ |
| Settings saved. | ސެޓިންގްސް ސޭވްކުރެވިއްޖެ. |
| You can view these settings but not change them. | ތިބާއަށް މި ސެޓިންގްސް ބެލޭނެ، ނަމަވެސް ބަދަލެއް ނުކުރެވޭނެ. |
| Leave blank to keep the current value. | މިހާރުގެ އަގު ބަދަލު ނުކުރަން ބޭނުންނަމަ ހުސްކޮށް ދޫކޮށްލައްވާ. |
| View settings | ސެޓިންގްސް ބެލުން |
| Edit settings | ސެޓިންގްސް ބަދަލުކުރުން |

The descriptions of per-page permissions ("Edit Mail settings") are generated from the page key and aren't translated; register the permission yourself with a translated description if you need one.
