<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Jobs\RecordSetting;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

/**
 * Starts a real `queue:work` process (through vendor/bin/testbench), changes a
 * setting from this process while the worker is running, and checks the
 * worker's next job sees the new value. Both processes share an SQLite file
 * for the settings, the queue and the cache.
 */
#[Group('worker')]
class QueueWorkerProcessTest extends TestCase
{
    protected string $directory;

    protected string $database;

    protected ?Process $worker = null;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->directory = sys_get_temp_dir() . '/javaabu-settings-worker-' . uniqid();

        (new Filesystem)->ensureDirectoryExists($this->directory);

        $this->database = $this->directory . '/database.sqlite';

        touch($this->database);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', $this->database);
        $app['config']->set('cache.default', 'database');
        $app['config']->set('queue.default', 'database');
        $app['config']->set('settings.store', 'database');
    }

    protected function tearDown(): void
    {
        $this->worker?->stop(0);

        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function it_gives_a_running_worker_the_settings_changed_by_another_process(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        Settings::set('site_name', 'ރާއްޖެ');

        $this->startWorker();

        RecordSetting::dispatch('site_name', "{$this->directory}/first.json");

        $this->assertEquals('ރާއްޖެ', $this->waitForRecording('first'));

        Settings::set('site_name', 'މާލެ');

        RecordSetting::dispatch('site_name', "{$this->directory}/second.json");

        $this->assertEquals('މާލެ', $this->waitForRecording('second'));
    }

    #[Test]
    public function it_leaves_a_running_worker_with_stale_settings_when_refreshing_is_off(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        Settings::set('site_name', 'ރާއްޖެ');

        $this->startWorker(['SETTINGS_REFRESH_QUEUE' => 'false']);

        RecordSetting::dispatch('site_name', "{$this->directory}/first.json");

        $this->assertEquals('ރާއްޖެ', $this->waitForRecording('first'));

        Settings::set('site_name', 'މާލެ');

        RecordSetting::dispatch('site_name', "{$this->directory}/second.json");

        $this->assertEquals('ރާއްޖެ', $this->waitForRecording('second'));
    }

    /**
     * @param  array<string, string>  $env
     */
    protected function startWorker(array $env = []): void
    {
        $this->worker = new Process(
            [PHP_BINARY, 'vendor/bin/testbench', 'queue:work', 'database', '--max-jobs=2', '--sleep=0', '--tries=1', '--timeout=30'],
            dirname(__DIR__, 2),
            [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $this->database,
                'CACHE_STORE' => 'database',
                'QUEUE_CONNECTION' => 'database',
                ...$env,
            ],
            timeout: 60,
        );

        $this->worker->start();
    }

    protected function waitForRecording(string $name): mixed
    {
        $path = "{$this->directory}/{$name}.json";
        $deadline = microtime(true) + 30;

        while (! file_exists($path)) {
            if (microtime(true) > $deadline || ! $this->worker->isRunning()) {
                $this->fail("The worker didn't run the {$name} job.\n" . $this->worker->getOutput() . $this->worker->getErrorOutput());
            }

            usleep(50_000);
        }

        return json_decode(file_get_contents($path), true);
    }
}
