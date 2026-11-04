{{--
    javaabu/settings guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/settings

- This project keeps app settings with `javaabu/settings`. Define settings with `Javaabu\Settings\Definitions\Setting::make()` on a `SettingsPage` (default, field and validation together), read them with `settings('key')` or the typed getters (`settings()->integer('per_page')`), and write them with `Settings::set([...])`. Don't add settings to `config/defaults.php`, query the `settings` table, or use anlutro's `Setting` facade.
- Always activate the `javaabu-settings` skill when adding, reading or changing a setting, building or changing a settings page, changing who may edit settings, or testing code that reads settings.
