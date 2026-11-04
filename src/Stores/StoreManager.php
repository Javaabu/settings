<?php

namespace Javaabu\Settings\Stores;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Manager;
use Javaabu\Settings\Contracts\SettingsStore;

/**
 * Creates the stores in `config('settings.stores')`. Add your own with
 * `Settings::extend('redis', fn ($app, array $config) => new RedisStore(...))`.
 *
 * @method SettingsStore driver(?string $driver = null)
 */
class StoreManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('settings.store', 'database');
    }

    public function store(?string $name = null): SettingsStore
    {
        return $this->driver($name);
    }

    protected function createDatabaseDriver(): DatabaseStore
    {
        $config = $this->storeConfig('database');

        return new DatabaseStore(
            $this->container->make('db')->connection($config['connection'] ?? null),
            $config['table'] ?? 'settings',
        );
    }

    protected function createJsonDriver(): JsonStore
    {
        $config = $this->storeConfig('json');

        return new JsonStore(
            $this->container->make(Filesystem::class),
            $config['path'] ?? storage_path('app/settings.json'),
        );
    }

    protected function createArrayDriver(): ArrayStore
    {
        return new ArrayStore;
    }

    /**
     * Custom creators get the app and the store's config
     */
    protected function callCustomCreator($driver): mixed
    {
        return $this->customCreators[$driver]($this->container, $this->storeConfig($driver));
    }

    /**
     * @return array<string, mixed>
     */
    protected function storeConfig(string $name): array
    {
        return (array) $this->config->get("settings.stores.{$name}", []);
    }
}
