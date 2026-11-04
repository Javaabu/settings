<?php

namespace Javaabu\Settings;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\Settings\Commands\ImportV1SettingsCommand;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Listeners\LogSettingsUpdate;
use Javaabu\Settings\Policies\SettingsPolicy;
use Javaabu\Settings\Routing\SettingsRoutes;
use Javaabu\Settings\Stores\StoreManager;

class SettingsServiceProvider extends ServiceProvider
{
    use RegistersAssets;

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
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'settings');

        $this->registerFrontendAssets();
        $this->registerPages();
        $this->registerRouteMacros();
        $this->registerPermissions();
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

    /**
     * The Inertia page (settings::Settings/Edit) and the components apps can
     * import as @javaabu/settings
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets('settings', __DIR__ . '/../resources', function (Package $package) {
            $package
                ->js()
                ->pages()
                ->npm([
                    '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                    '@javaabu/onu' => '^0.2.1',
                ], peer_dependencies: [
                    'react' => '^19.0',
                    'react-dom' => '^19.0',
                    '@inertiajs/react' => '^2.0 || ^3.0',
                ]);
        });
    }

    protected function registerPages(): void
    {
        $pages = (array) config('settings.pages', []);

        if ($pages !== []) {
            $this->app->make(SettingsRegistry::class)->pages($pages);
        }
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('settings', function (array $options = []) {
            SettingsRoutes::register($this, $options);
        });
    }

    /**
     * view_settings and edit_settings, the permissions of pages that have their
     * own, and the default policy, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (! config('settings.register_permissions', true)) {
            return;
        }

        Permissions::register('settings', [
            SettingsPolicy::VIEW => 'View settings',
            SettingsPolicy::EDIT => 'Edit settings',
        ], label: 'Settings');

        Permissions::policy(SettingsPage::class, config('settings.policy', SettingsPolicy::class));

        $this->app->booted(fn () => $this->registerPagePermissions());
    }

    /**
     * Pages registered by the app may need their own permission
     */
    protected function registerPagePermissions(): void
    {
        $group = Permissions::group('settings');

        foreach ($this->app->make(SettingsRegistry::class)->all() as $page) {
            $permission = $page->getPermission();

            if ($permission === null || Permissions::has($permission)) {
                continue;
            }

            $label = Str::headline($page->getKey());

            $group?->add([$permission => "Edit {$label} settings"]);
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
            __DIR__ . '/../resources/views' => resource_path('views/vendor/settings'),
        ], 'settings-views');

        $this->publishes([
            __DIR__ . '/../config/defaults.php' => config_path('defaults.php'),
        ], 'settings-defaults');
    }
}
