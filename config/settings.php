<?php

use Javaabu\Settings\Http\Controllers\SettingsController;
use Javaabu\Settings\Policies\SettingsPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    |
    | Where values are kept: database (the settings table), json (a file) or
    | array (memory only, for tests). Add your own with Settings::extend().
    |
    */

    'store' => env('SETTINGS_STORE', 'database'),

    'stores' => [
        'database' => [
            'connection' => env('SETTINGS_DB_CONNECTION'),
            'table' => 'settings',
        ],

        'json' => [
            'path' => storage_path('app/settings.json'),
        ],

        'array' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Values are cached forever and forgotten when a setting changes, so a
    | request reads the store at most once. store null means the app's default
    | cache store. Use a store every process shares (redis, database, file)
    | so queue workers and Octane see changes: see refresh below.
    |
    */

    'cache' => [
        'enabled' => env('SETTINGS_CACHE', true),
        'store' => env('SETTINGS_CACHE_STORE'),
        'prefix' => 'javaabu_settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Refreshing long-running processes
    |--------------------------------------------------------------------------
    |
    | Every change puts a new version in the cache. Queue workers check it
    | before each job and Octane before each request, task and tick, and
    | reload the settings when it changed.
    |
    */

    'refresh' => [
        'queue' => env('SETTINGS_REFRESH_QUEUE', true),
        'octane' => env('SETTINGS_REFRESH_OCTANE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | SettingsPage classes to register. You can also register pages from a
    | service provider with Settings::page().
    |
    */

    'pages' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Files
    |--------------------------------------------------------------------------
    |
    | Where file and image settings are uploaded. disk null means the app's
    | default filesystem disk; use a public disk for files shown on the site.
    |
    */

    'disk' => env('SETTINGS_DISK', 'public'),

    'directory' => 'settings',

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The pages the settings routes render: blade (javaabu/forms components,
    | through stencil's Blade driver) or inertia (React pages shipped through
    | javaabu/manifest). Requests that expect JSON always get JSON.
    |
    */

    'views' => env('SETTINGS_VIEWS', 'blade'),

    /*
    | The Blade layout the pages extend, and the section they fill.
    */

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace the controller with a subclass, or pass `controller` to
    | Route::settings(). The policy here is the package default; apps can
    | also override it with config('permissions.policies') or Gate::policy().
    | Turn off register_permissions to register your own.
    |
    */

    'controllers' => [
        'settings' => SettingsController::class,
    ],

    'policy' => SettingsPolicy::class,

    'register_permissions' => true,

    /*
    |--------------------------------------------------------------------------
    | Activity log
    |--------------------------------------------------------------------------
    |
    | With spatie/laravel-activitylog installed, every change is logged with
    | the old and new values, caused by the user who made it.
    |
    */

    'log_activity' => true,

];
