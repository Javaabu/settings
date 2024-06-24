<?php

use anlutro\LaravelSettings\Facades\Setting;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;

if (! function_exists('default_setting')) {
    /**
     * Returns the default setting
     * If setting does not exist, return empty string
     *
     * @param string $key
     * @return string
     */
    function default_setting(string $key): string
    {
        $default = config('defaults.'.$key);
        return is_null($default) ? '' : $default;
    }
}

if (! function_exists('get_setting')) {
    /**
     * Shortcut for getting setting value
     *
     * @param string $key
     * @return mixed
     */
    function get_setting(string $key): mixed
    {
        return Setting::get($key, default_setting($key));
    }
}

if (! function_exists('set_file_setting')) {
    /**
     * Saves a file settings
     *
     * @param string $key
     * @param string $path
     * @return bool
     */
    function set_file_setting(string $key, string $path = 'storage/uploads'): bool
    {
        if (RequestFacade::hasFile($key) && RequestFacade::file($key)->isValid()) {
            $extension = RequestFacade::file($key)->guessExtension(); //get file extension
            $file_name = 'custom-'.$key.'-'.strtolower(Str::random(8)).'.'.$extension; // renaming file
            RequestFacade::file($key)->move($path, $file_name); // uploading file to given path
            Setting::set($key, $path.'/'.$file_name);
            return true;
        } elseif (RequestFacade::exists($key)) {
            reset_file_setting($key);
            return true;
        }
        
        return false;
    }
}

if (! function_exists('reset_file_setting')) {
    /**
     * Resets a file settings
     *
     * @param string $key
     *
     * @throws FilesystemException
     */
    function reset_file_setting(string $key): void
    {
        $current_file = get_setting($key);
        $default = default_setting($key);

        //delete the custom file if different from default
        if ($current_file != $default) {
            // get relative path
            $current_file = remove_prefix('storage', $current_file);

            if (Storage::disk('public')->exists($current_file)) {
                Storage::disk('public')->delete($current_file);
            }

            Setting::set($key, $default);
        }
    }
}

if (! function_exists('remove_prefix')) {
    /**
     * Remove the given prefix from a string
     * @param $needle
     * @param $haystack
     * @return bool|string
     */
    function remove_prefix($needle, $haystack): bool|string
    {
        if (str_starts_with($haystack, $needle)) {
            $haystack = substr($haystack, strlen($needle));
        }

        return $haystack;
    }
}

