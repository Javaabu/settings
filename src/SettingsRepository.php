<?php

namespace Javaabu\Settings;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Javaabu\Settings\Contracts\SettingsStore;
use Javaabu\Settings\Definitions\InlinePage;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Settings\Stores\ArrayStore;
use Javaabu\Settings\Stores\StoreManager;
use Javaabu\Settings\Support\Translations;

/**
 * Reads and writes settings. Values are loaded once per process (from the cache,
 * or the store), and reloaded when another process changes them: see
 * refreshIfStale().
 */
class SettingsRepository
{
    /** @var array<string, mixed>|null the stored values, as the store keeps them */
    protected ?array $values = null;

    /** The cache version the values were loaded at */
    protected ?string $version = null;

    protected ?ArrayStore $fake = null;

    /** @var array<string, mixed> */
    protected array $default_overrides = [];

    public function __construct(
        protected Container $app,
        protected SettingsRegistry $registry,
        protected StoreManager $stores,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Reading
    |--------------------------------------------------------------------------
    */

    /**
     * The value of a setting: the stored value, else its default (from its
     * definition, then config('defaults')), else $default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->stored();

        if (! array_key_exists($key, $stored)) {
            return $this->default($key) ?? value($default);
        }

        return $this->read($key, $stored[$key]);
    }

    /**
     * The value of a translatable setting in a locale
     */
    public function translate(string $key, ?string $locale = null, bool $fallback = true): mixed
    {
        $stored = $this->stored();

        if (! array_key_exists($key, $stored)) {
            return $this->default($key);
        }

        $value = $this->decrypt($key, $stored[$key]);

        if (Translations::isLocaleMap($value)) {
            $value = Translations::pick($value, $locale, $fallback);
        }

        return $this->registry->setting($key)?->castValue($value) ?? $value;
    }

    /**
     * Every value of a translatable setting, keyed by locale
     *
     * @return array<string, mixed>
     */
    public function translations(string $key): array
    {
        $stored = $this->stored();

        $value = array_key_exists($key, $stored)
            ? $this->decrypt($key, $stored[$key])
            : $this->default($key);

        if (Translations::isLocaleMap($value)) {
            return $value;
        }

        return $value === null ? [] : [Translations::defaultLocale() => $value];
    }

    /**
     * The default value of a setting
     */
    public function default(string $key): mixed
    {
        if (array_key_exists($key, $this->default_overrides)) {
            return $this->default_overrides[$key];
        }

        $setting = $this->registry->setting($key);

        $default = $setting?->getDefault();

        if ($default !== null) {
            return $default;
        }

        $default = $this->app['config']->get("defaults.{$key}");

        return $setting && $default !== null ? $setting->castValue($default) : $default;
    }

    /**
     * Whether the setting has a stored value
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->stored());
    }

    /**
     * Every setting: the defined ones, the ones with a stored value, and the
     * ones in config('defaults')
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $keys = array_unique([
            ...array_keys($this->registry->settings()),
            ...array_keys($this->stored()),
            ...array_keys((array) $this->app['config']->get('defaults', [])),
        ]);

        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * The values of some settings
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * The stored values as the store keeps them (encrypted settings stay encrypted), keyed by setting key
     *
     * @return array<string, mixed>
     */
    public function stored(): array
    {
        if ($this->values === null) {
            $this->load();
        }

        return $this->values;
    }

    public function string(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if (! is_string($value)) {
            throw SettingsException::wrongType($key, 'string', $value);
        }

        return $value;
    }

    public function integer(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if (! is_int($value)) {
            throw SettingsException::wrongType($key, 'int', $value);
        }

        return $value;
    }

    public function float(string $key, ?float $default = null): float
    {
        $value = $this->get($key, $default);

        if (is_int($value)) {
            return (float) $value;
        }

        if (! is_float($value)) {
            throw SettingsException::wrongType($key, 'float', $value);
        }

        return $value;
    }

    public function boolean(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if (! is_bool($value)) {
            throw SettingsException::wrongType($key, 'bool', $value);
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>|null  $default
     * @return array<array-key, mixed>
     */
    public function array(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value)) {
            throw SettingsException::wrongType($key, 'array', $value);
        }

        return $value;
    }

    /**
     * The URL of a file setting: an upload on config('settings.disk'), or a
     * path in public/ (for defaults such as img/logo.png)
     */
    public function url(string $key): ?string
    {
        $path = $this->get($key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        $disk = $this->app['config']->get('settings.disk');

        if ($path !== $this->default($key) && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->url($path);
        }

        return asset($path);
    }

    /*
    |--------------------------------------------------------------------------
    | Writing
    |--------------------------------------------------------------------------
    */

    /**
     * Store values and tell other processes they changed
     *
     * @param  string|array<string, mixed>  $key
     * @param  Model|null  $causer  who made the change, for the activity log; the
     *                              authenticated user when null
     */
    public function set(string|array $key, mixed $value = null, ?Model $causer = null): static
    {
        $values = is_array($key) ? $key : [$key => $value];

        if ($values === []) {
            return $this;
        }

        $old = $this->only(array_keys($values));

        $storable = [];

        foreach ($values as $setting_key => $setting_value) {
            $storable[$setting_key] = $this->storable($setting_key, $setting_value);
        }

        $this->store()->put($storable);

        $this->changed($old, $causer);

        return $this;
    }

    /**
     * Remove stored values, so the settings are back to their defaults
     *
     * @param  string|array<int, string>  $keys
     */
    public function forget(string|array $keys, ?Model $causer = null): static
    {
        $keys = (array) $keys;

        if ($keys === []) {
            return $this;
        }

        $old = $this->only($keys);

        $this->store()->forget($keys);

        $this->changed($old, $causer);

        return $this;
    }

    /**
     * Remove every stored value
     */
    public function flush(): static
    {
        $this->store()->flush();

        $this->touch();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Long-running processes
    |--------------------------------------------------------------------------
    */

    /**
     * Reload the values if another process changed them since they were loaded.
     * Queue workers call it before each job and Octane before each request.
     *
     * @return bool whether the values were stale
     */
    public function refreshIfStale(): bool
    {
        if ($this->values === null) {
            return false;
        }

        $cache = $this->cache();

        if ($cache !== null && $cache->get($this->versionKey()) === $this->version) {
            return false;
        }

        $this->refresh();

        return true;
    }

    /**
     * Forget the loaded values, so they're read again when next needed
     */
    public function refresh(): static
    {
        $this->values = null;
        $this->version = null;

        return $this;
    }

    /**
     * The version the values were loaded at, or null when they aren't loaded
     * or there's no cache
     */
    public function version(): ?string
    {
        return $this->version;
    }

    /**
     * Mark the settings as changed: other processes reload them
     */
    public function touch(): static
    {
        $cache = $this->cache();

        if ($cache !== null) {
            $previous = $cache->get($this->versionKey());

            if (is_string($previous)) {
                $cache->forget($this->valuesKey($previous));
            }

            $cache->forever($this->versionKey(), (string) Str::uuid());
        }

        return $this->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Definitions
    |--------------------------------------------------------------------------
    */

    /**
     * Register a page class or instance, or get (or start) an inline page by key
     *
     * @param  SettingsPage|class-string<SettingsPage>|string  $page
     * @return ($page is string ? InlinePage|SettingsPage : SettingsPage)
     */
    public function page(SettingsPage|string $page): SettingsPage
    {
        return $this->registry->page($page);
    }

    /**
     * Define settings that have no page
     *
     * @param  array<int, Setting>|Setting  $settings
     */
    public function define(array|Setting $settings): static
    {
        $this->registry->define($settings);

        return $this;
    }

    /**
     * @return array<string, SettingsPage>
     */
    public function pages(): array
    {
        return $this->registry->all();
    }

    public function definition(string $key): ?Setting
    {
        return $this->registry->setting($key);
    }

    public function registry(): SettingsRegistry
    {
        return $this->registry;
    }

    /*
    |--------------------------------------------------------------------------
    | Stores
    |--------------------------------------------------------------------------
    */

    public function store(): SettingsStore
    {
        return $this->fake ?? $this->stores->store();
    }

    /**
     * Add a store driver
     *
     * @param  Closure(Container, array<string, mixed>): SettingsStore  $creator
     */
    public function extend(string $driver, Closure $creator): static
    {
        $this->stores->extend($driver, $creator);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */

    /**
     * Keep values in memory, without the store or the cache, starting with these
     *
     * @param  array<string, mixed>  $values
     */
    public function fake(array $values = []): static
    {
        $this->fake ??= new ArrayStore;

        $storable = [];

        foreach ($values as $key => $value) {
            $storable[$key] = $this->storable($key, $value);
        }

        $this->fake->put($storable);

        return $this->refresh();
    }

    public function isFaking(): bool
    {
        return $this->fake !== null;
    }

    /**
     * Use this default for a setting, whatever its definition says
     */
    public function fakeDefault(string $key, mixed $value): static
    {
        $this->default_overrides[$key] = $value;

        return $this;
    }

    /**
     * Stop faking values and defaults
     */
    public function stopFaking(): static
    {
        $this->fake = null;
        $this->default_overrides = [];

        return $this->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    protected function load(): void
    {
        $cache = $this->cache();

        if ($cache === null) {
            $this->values = $this->store()->all();
            $this->version = null;

            return;
        }

        $version = $cache->get($this->versionKey());

        if (! is_string($version)) {
            $version = (string) Str::uuid();

            $cache->forever($this->versionKey(), $version);
        }

        $this->version = $version;

        $this->values = $cache->remember($this->valuesKey($version), now()->addWeek(), fn () => $this->store()->all());
    }

    /**
     * After a write: tell other processes, and fire SettingsUpdated if anything changed
     *
     * @param  array<string, mixed>  $old
     */
    protected function changed(array $old, ?Model $causer): void
    {
        $this->touch();

        $new = $this->only(array_keys($old));

        if ($new == $old) {
            return;
        }

        $causer ??= $this->authenticatedUser();

        $this->app['events']->dispatch(new SettingsUpdated($old, $new, $causer));
    }

    /**
     * A stored value as the app reads it
     */
    protected function read(string $key, mixed $value): mixed
    {
        $value = $this->decrypt($key, $value);

        $setting = $this->registry->setting($key);

        if ($setting === null) {
            return $value;
        }

        if ($setting->isDeclaredTranslatable() && Translations::isLocaleMap($value)) {
            $value = Translations::pick($value);
        }

        return $setting->castValue($value);
    }

    protected function storable(string $key, mixed $value): mixed
    {
        $setting = $this->registry->setting($key);

        if ($setting === null) {
            return $value;
        }

        $value = $setting->storableValue($value);

        if ($setting->isEncrypted() && $value !== null) {
            return $this->app['encrypter']->encrypt($value);
        }

        return $value;
    }

    protected function decrypt(string $key, mixed $value): mixed
    {
        if (! is_string($value) || ! $this->registry->setting($key)?->isEncrypted()) {
            return $value;
        }

        try {
            return $this->app['encrypter']->decrypt($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    protected function cache(): ?Cache
    {
        if ($this->fake !== null) {
            return null;
        }

        $config = $this->app['config'];

        if (! $config->get('settings.cache.enabled', true)) {
            return null;
        }

        return $this->app['cache']->store($config->get('settings.cache.store'));
    }

    protected function versionKey(): string
    {
        return $this->cachePrefix() . '.version';
    }

    protected function valuesKey(string $version): string
    {
        return $this->cachePrefix() . ".values.{$version}";
    }

    protected function cachePrefix(): string
    {
        return (string) $this->app['config']->get('settings.cache.prefix', 'javaabu_settings');
    }

    protected function authenticatedUser(): ?Model
    {
        if (! $this->app->bound('auth')) {
            return null;
        }

        $user = $this->app['auth']->user();

        return $user instanceof Model ? $user : null;
    }
}
