---
title: Stores and caching
sidebar_position: 2
---

# Stores

| Store | Keeps values | Use |
| --- | --- | --- |
| `database` (default) | In the `settings` table, a row per setting with the value as JSON | Most apps |
| `json` | In a JSON file (`storage/app/settings.json`), written atomically | Apps without a database; a single server |
| `array` | In memory, for the request | Tests |

```php
// config/settings.php
'store' => 'json',
'stores' => [
    'json' => ['path' => storage_path('app/settings.json')],
],
```

## Your own store

Implement `Javaabu\Settings\Contracts\SettingsStore` and register it as a driver, in a service provider's `register()` or `boot()`:

```php
use Javaabu\Settings\Contracts\SettingsStore;
use Javaabu\Settings\Facades\Settings;

class DynamoDbStore implements SettingsStore
{
    public function all(): array { /* every value, keyed by setting key */ }
    public function put(array $values): void { /* store these values */ }
    public function forget(array $keys): void { /* remove these keys */ }
    public function flush(): void { /* remove everything */ }
}

Settings::extend('dynamodb', fn ($app, array $config) => new DynamoDbStore($config['table']));
```

```php
// config/settings.php
'store' => 'dynamodb',
'stores' => [
    'dynamodb' => ['table' => 'settings'],
],
```

The closure gets the app and `config('settings.stores.{driver}')`. `Settings::store()` returns the store in use.

# Caching

The first read in a process loads every value: from the cache when it has them, otherwise from the store, which is then cached. Reads after that come from memory, so `settings()` costs nothing in a loop.

The cache holds a **version**, and the values are cached under it. Every write stores the values, then puts a new version in the cache. Processes that loaded the old version see the new one and reload (see [Queue workers and Octane](queue-workers-and-octane.md)). Because the values are keyed by version, a process that read the store just before a write can't cache stale values under the new version.

- Use a cache store every process and server shares: Redis, Memcached, database or (on one server) file. With `array`, each process has its own cache.
- `cache.enabled` off reads the store once per request or job instead.
- Clearing the cache (`php artisan cache:clear`) is safe: values are read from the store again.

`Settings::refresh()` forgets what the current process loaded; `Settings::touch()` tells every process to reload.

# The table

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->longText('value')->nullable(); // JSON
    $table->timestamps();
});
```

Values are JSON-encoded, so types survive: `20` is an integer, `true` a boolean, lists are arrays. Encrypted settings hold the encrypted string.
