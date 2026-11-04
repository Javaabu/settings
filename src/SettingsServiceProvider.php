<?php

namespace Javaabu\Settings;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Javaabu\Settings\Commands\ImportV1SettingsCommand;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Listeners\LogSettingsUpdate;
use Javaabu\Settings\Stores\StoreManager;

class SettingsServiceProvider extends ServiceProvider
{
    /** The Octane events after which a worker checks for changed settings */
    public const array OCTANE_EVENTS = [
        'Laravel\Octane\Events\RequestReceived',
        'Laravel\Octane\Events\TaskReceived',
        'Laravel\Octane\Events\TickReceived',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/settings.php', 'settings');

        $this->app->singleton(StoreManager::class);
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsRepository::class);
        $this->app->alias(SettingsRepository::class, 'settings');
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');

        $this->registerPages();
        $this->registerRefreshing();
        $this->registerActivityLog();

        if ($this->app->runningInConsole()) {
            if (Settings::$runs_migrations) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();

            $this->commands([
                ImportV1SettingsCommand::class,
            ]);
        }
    }

    protected function registerPages(): void
    {
        $pages = (array) config('settings.pages', []);

        if ($pages !== []) {
            $this->app->make(SettingsRegistry::class)->pages($pages);
        }
    }

    /**
     * Queue workers and Octane reload settings another process changed
     */
    protected function registerRefreshing(): void
    {
        $refresh = fn () => $this->app->make(SettingsRepository::class)->refreshIfStale();

        if (config('settings.refresh.queue', true)) {
            Queue::before($refresh);
        }

        if (config('settings.refresh.octane', true)) {
            Event::listen(self::OCTANE_EVENTS, $refresh);
        }
    }

    protected function registerActivityLog(): void
    {
        if (! config('settings.log_activity', true)) {
            return;
        }

        if (! function_exists('activity')) {
            return;
        }

        Event::listen(SettingsUpdated::class, LogSettingsUpdate::class);
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'settings-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/settings'),
        ], 'settings-translations');

        $this->publishes([
            __DIR__ . '/../config/defaults.php' => config_path('defaults.php'),
        ], 'settings-defaults');
    }
}
