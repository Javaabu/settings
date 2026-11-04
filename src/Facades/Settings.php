<?php

namespace Javaabu\Settings\Facades;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Javaabu\Settings\Contracts\SettingsStore;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\SettingsRegistry;
use Javaabu\Settings\SettingsRepository;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed translate(string $key, ?string $locale = null, bool $fallback = true)
 * @method static array<string, mixed> translations(string $key)
 * @method static mixed default(string $key)
 * @method static bool has(string $key)
 * @method static array<string, mixed> all()
 * @method static array<string, mixed> only(array $keys)
 * @method static array<string, mixed> stored()
 * @method static string string(string $key, ?string $default = null)
 * @method static int integer(string $key, ?int $default = null)
 * @method static float float(string $key, ?float $default = null)
 * @method static bool boolean(string $key, ?bool $default = null)
 * @method static array array(string $key, ?array $default = null)
 * @method static string|null url(string $key)
 * @method static SettingsRepository set(string|array $key, mixed $value = null, ?Model $causer = null)
 * @method static SettingsRepository forget(string|array $keys, ?Model $causer = null)
 * @method static SettingsRepository flush()
 * @method static bool refreshIfStale()
 * @method static SettingsRepository refresh()
 * @method static string|null version()
 * @method static SettingsRepository touch()
 * @method static SettingsPage page(SettingsPage|string $page)
 * @method static SettingsRepository define(array|Setting $settings)
 * @method static array<string, SettingsPage> pages()
 * @method static Setting|null definition(string $key)
 * @method static SettingsRegistry registry()
 * @method static SettingsStore store()
 * @method static SettingsRepository extend(string $driver, Closure $creator)
 * @method static SettingsRepository fake(array $values = [])
 * @method static bool isFaking()
 * @method static SettingsRepository fakeDefault(string $key, mixed $value)
 * @method static SettingsRepository stopFaking()
 *
 * @see SettingsRepository
 */
class Settings extends Facade
{
    public static bool $runs_migrations = true;

    /**
     * Stop the package from loading its migration, e.g. after publishing it
     */
    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    protected static function getFacadeAccessor(): string
    {
        return SettingsRepository::class;
    }
}
