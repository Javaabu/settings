<?php

use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Javaabu\Settings\SettingsRepository;

if (! function_exists('settings')) {
    /**
     * The settings repository, a setting's value, or set several settings
     *
     * ```php
     * settings('site_name');                       // the value
     * settings('per_page', 15);                    // with a fallback
     * settings(['site_name' => 'ރާއްޖެ']);           // set
     * settings()->boolean('registration_open');    // typed
     * ```
     *
     * @param  string|array<string, mixed>|null  $key
     */
    function settings(string|array|null $key = null, mixed $default = null): mixed
    {
        $settings = app(SettingsRepository::class);

        if ($key === null) {
            return $settings;
        }

        if (is_array($key)) {
            return $settings->set($key);
        }

        return $settings->get($key, $default);
    }
}

if (! function_exists('default_setting')) {
    /**
     * The default value of a setting: its definition's default, or config('defaults.{key}')
     */
    function default_setting(string $key): mixed
    {
        return app(SettingsRepository::class)->default($key);
    }
}

if (! function_exists('get_setting')) {
    /**
     * The value of a setting, or its default. Same as settings($key)
     */
    function get_setting(string $key): mixed
    {
        return app(SettingsRepository::class)->get($key);
    }
}

if (! function_exists('set_file_setting')) {
    /**
     * Saves the file uploaded in the current request under the setting's key, moved
     * into public $path. An empty value in the request resets the setting to its default.
     *
     * Kept from v1. Settings pages upload file settings for you.
     */
    function set_file_setting(string $key, string $path = 'storage/uploads'): bool
    {
        if (RequestFacade::hasFile($key) && RequestFacade::file($key)->isValid()) {
            $file = RequestFacade::file($key);
            $extension = $file->guessExtension();
            $file_name = 'custom-' . $key . '-' . strtolower(Str::random(8)) . '.' . $extension;

            $file->move(public_path($path), $file_name);

            app(SettingsRepository::class)->set($key, "{$path}/{$file_name}");

            return true;
        }

        if (RequestFacade::exists($key)) {
            reset_file_setting($key);

            return true;
        }

        return false;
    }
}

if (! function_exists('reset_file_setting')) {
    /**
     * Deletes the uploaded file of a file setting (from the public disk) and resets
     * the setting to its default. Kept from v1.
     */
    function reset_file_setting(string $key): void
    {
        $settings = app(SettingsRepository::class);

        $current_file = $settings->get($key);
        $default = $settings->default($key);

        if ($current_file == $default) {
            return;
        }

        if (is_string($current_file)) {
            $relative = ltrim(remove_prefix('storage', $current_file), '/');

            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }

        $settings->forget($key);
    }
}

if (! function_exists('remove_prefix')) {
    /**
     * Remove the prefix from the start of a string. Kept from v1.
     */
    function remove_prefix(string $needle, string $haystack): string
    {
        if (str_starts_with($haystack, $needle)) {
            return substr($haystack, strlen($needle));
        }

        return $haystack;
    }
}
