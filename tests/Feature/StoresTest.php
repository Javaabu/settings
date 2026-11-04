<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Settings\Contracts\SettingsStore;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\SettingsRepository;
use Javaabu\Settings\Stores\ArrayStore;
use Javaabu\Settings\Stores\DatabaseStore;
use Javaabu\Settings\Stores\JsonStore;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;

class StoresTest extends TestCase
{
    use RefreshDatabase;

    protected string $json_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->json_path = sys_get_temp_dir() . '/javaabu-settings-' . uniqid() . '/settings.json';
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory(dirname($this->json_path));

        parent::tearDown();
    }

    #[Test]
    public function it_keeps_values_in_the_database(): void
    {
        $this->app['config']->set('settings.store', 'database');

        $store = Settings::store();

        $this->assertInstanceOf(DatabaseStore::class, $store);

        $this->assertStoreWorks($store);

        $store->put(['site_name' => 'ރާއްޖެ']);

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => '"ރާއްޖެ"']);
    }

    #[Test]
    public function it_keeps_values_in_a_json_file(): void
    {
        $this->app['config']->set('settings.store', 'json');
        $this->app['config']->set('settings.stores.json.path', $this->json_path);

        $store = Settings::store();

        $this->assertInstanceOf(JsonStore::class, $store);
        $this->assertEquals([], $store->all());

        $this->assertStoreWorks($store);

        $store->put(['site_name' => 'ރާއްޖެ']);

        $this->assertStringContainsString('"site_name": "ރާއްޖެ"', file_get_contents($this->json_path));
    }

    #[Test]
    public function it_rejects_a_json_file_that_isnt_an_object(): void
    {
        (new Filesystem)->ensureDirectoryExists(dirname($this->json_path));
        file_put_contents($this->json_path, '"text"');

        $this->expectException(SettingsException::class);

        (new JsonStore(new Filesystem, $this->json_path))->all();
    }

    #[Test]
    public function it_keeps_values_in_memory(): void
    {
        $this->assertStoreWorks(new ArrayStore);
    }

    #[Test]
    public function it_adds_store_drivers(): void
    {
        $this->app['config']->set('settings.store', 'custom');
        $this->app['config']->set('settings.stores.custom', ['values' => ['site_name' => 'Custom']]);

        Settings::extend('custom', fn ($app, array $config) => new ArrayStore($config['values']));

        $this->assertEquals('Custom', Settings::get('site_name'));
    }

    #[Test]
    public function it_reads_the_store_once_and_caches_the_values(): void
    {
        $this->app['config']->set('settings.store', 'database');

        Settings::page(GeneralSettings::class);
        Settings::set('site_name', 'މާލެ');

        $this->app['cache']->store()->flush();

        $queries = 0;
        $this->app['db']->listen(function () use (&$queries) {
            $queries++;
        });

        $other = $this->newRepository();

        $this->assertEquals('މާލެ', $other->get('site_name'));
        $this->assertEquals(20, $other->get('per_page'));
        $this->assertEquals(1, $queries);

        $third = $this->newRepository();

        $this->assertEquals('މާލެ', $third->get('site_name'));
        $this->assertEquals(1, $queries);
    }

    #[Test]
    public function it_reads_the_store_every_time_without_the_cache(): void
    {
        $this->app['config']->set('settings.store', 'database');
        $this->app['config']->set('settings.cache.enabled', false);

        Settings::set('site_name', 'މާލެ');

        $queries = 0;
        $this->app['db']->listen(function () use (&$queries) {
            $queries++;
        });

        $this->newRepository()->get('site_name');
        $this->newRepository()->get('site_name');

        $this->assertEquals(2, $queries);
        $this->assertNull(Settings::version());
    }

    #[Test]
    public function it_flushes_every_value(): void
    {
        Settings::set(['site_name' => 'މާލެ', 'other' => 1]);

        Settings::flush();

        $this->assertEquals([], Settings::stored());
    }

    protected function assertStoreWorks(SettingsStore $store): void
    {
        $store->put(['site_name' => 'ރާއްޖެ', 'per_page' => 20, 'keywords' => ['a', 'b'], 'open' => true, 'empty' => null]);

        $this->assertSame(['site_name' => 'ރާއްޖެ', 'per_page' => 20, 'keywords' => ['a', 'b'], 'open' => true, 'empty' => null], $this->sorted($store->all(), ['site_name', 'per_page', 'keywords', 'open', 'empty']));

        $store->put(['per_page' => 30]);
        $this->assertSame(30, $store->all()['per_page']);

        $store->forget(['keywords', 'empty']);
        $this->assertArrayNotHasKey('keywords', $store->all());
        $this->assertArrayNotHasKey('empty', $store->all());

        $store->put([]);
        $store->forget([]);

        $store->flush();
        $this->assertEquals([], $store->all());
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function sorted(array $values, array $keys): array
    {
        return array_merge(array_flip($keys), $values);
    }

    protected function newRepository(): SettingsRepository
    {
        return $this->app->build(SettingsRepository::class);
    }
}
