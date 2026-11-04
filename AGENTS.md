# settings: notes for AI coding agents

`javaabu/settings` keeps app settings: defined with javaabu/stencil fields (`Setting::make()`), grouped into `SettingsPage`s and `Section`s, stored by a `SettingsStore` (database, json, array), cached under a version token that queue workers and Octane check, and edited through `Route::settings()` pages in Blade or Inertia React with a policy and permissions from javaabu/permissions. Apps extend `SettingsPage`, `SettingsController` and `SettingsPolicy`, and other Javaabu packages call the v1 helpers, so treat `Setting`, `Section`, `SettingsPage`, `SettingsRepository`/`Settings`, the helpers in `src/helpers.php`, `SettingsStore`, `SettingsUpdated`, `FakesSettings`, `Route::settings()` and the page props as public contracts.

## Layout

| Path | What |
| --- | --- |
| `src/SettingsRepository.php`, `src/Facades/Settings.php`, `src/helpers.php` | Reading, writing, typed getters, versioned cache, fakes; `settings()` and the v1 helpers |
| `src/SettingsRegistry.php`, `src/Definitions` | Pages and the index of settings; `Setting`, `Section`, `SettingsPage`, `InlinePage` |
| `src/Stores`, `src/Contracts` | The stores and their manager |
| `src/Forms/SettingsForm.php` | The stencil form of a page: values, saving, files, encrypted and translatable settings |
| `src/Routing`, `src/Http`, `src/Policies`, `src/Views` | `Route::settings()`, the controller, the policy, `ViewRenderer` |
| `src/Support` | Optional javaabu/translatable support (`Translations`, `Translatable\SettingsRecord`) |
| `src/Events`, `src/Listeners`, `src/Commands`, `src/Testing` | `SettingsUpdated`, the activity log listener, `settings:import-v1`, `FakesSettings` |
| `config/settings.php`, `config/defaults.php`, `database/migrations` | Options; the v1 defaults sample; the `settings` table |
| `resources/views` | The Blade page (javaabu/forms components, stencil's Blade driver) |
| `resources/js` | The Inertia React page (TypeScript), shipped through javaabu/manifest |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `docs`, `llms.txt` | Docusaurus pages for docs.javaabu.com and the index for AI assistants |
| `resources/boost` | Laravel Boost guideline and skill, shipped in the package |
| `tests` | Testbench tests; two guards (`web` → `User`, `web_admin` → `Admin`); `QueueWorkerProcessTest` starts a real worker with `vendor/bin/testbench` (`testbench.yaml`); `tests/js` are Vitest tests of the React page |

## Rules

- Follow Javaabu codestyle ([boost-javaabu-guidelines](https://github.com/Javaabu/boost-javaabu-guidelines)): snake_case variables and properties, camelCase methods, early returns, `#[Test]` and `it_…` test names. Pint enforces formatting.
- Every write must go through `SettingsRepository::set()`/`forget()`/`flush()` so the cache version changes; never cache values outside the versioned keys.
- Nothing may assume the `web` guard or `App\Models\User`. Type-hint users as `Model`; test on both guards.
- Inertia, javaabu/translatable and spatie/laravel-activitylog are optional: feature-detect them (translatable only through `Support\Translations`), keep them in `suggest`, and test both ways. Test classes must load without them (no `use` of their traits).
- Keep the v1 helpers and `FakesSettings` working; document any break in `docs/upgrading.md`.
- Encrypted values must never reach the browser or the activity log.
- Every user-facing string goes through `__()` with an English key, and gets a line in `lang/en.json` and `lang/dv.json` (`TranslationsTest` checks). Use Thaana in tests of user-facing behaviour. List uncertain translations in `docs/advanced-usage/dhivehi-and-rtl.md`. Views use logical CSS only (`ms-`, `pe-`, `start-`), never `left`/`right`.
- When the Inertia props change, regenerate the JS fixture: `UPDATE_FIXTURES=1 vendor/bin/phpunit --filter InertiaPagesTest`.
- Every change needs tests, docs (linked from `llms.txt`), the Boost skill if it changes how the package is used, and a line in `CHANGELOG.md`.
- Work on the `next-gen` branch; don't commit to `main`.

## Commands

```bash
composer install
composer lint       # vendor/bin/pint --test
composer format     # vendor/bin/pint
composer test       # vendor/bin/phpunit
pnpm install && pnpm check   # typecheck and Vitest for resources/js (needs vendor/ for stencil)
```
