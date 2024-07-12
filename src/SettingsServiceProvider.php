<?php

namespace Javaabu\Settings;

use Illuminate\Support\ServiceProvider;
use anlutro\LaravelSettings\ServiceProvider as AluntroSettingsServiceProvider;
use Javaabu\Settings\Providers\EventServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/defaults.php' => config_path('defaults.php'),
            ], 'settings-defaults');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Load the original settings package
        $this->app->register(AluntroSettingsServiceProvider::class);

        $this->app->register(EventServiceProvider::class);

        // Require helpers defined on the package.
        require_once __DIR__ . '/helpers/helpers.php';
    }
}
