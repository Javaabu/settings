<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\WorkerOptions;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\SettingsRepository;
use Javaabu\Settings\SettingsServiceProvider;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Jobs\RecordSetting;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * A worker runs jobs with the settings singleton it loaded earlier. Another
 * process (a request) changes a setting in between: the next job must see it.
 */
class QueueRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('settings.store', 'database');
        $app['config']->set('queue.default', 'database');

        if ($this->name() === 'it_keeps_stale_values_when_refreshing_is_off') {
            $app['config']->set('settings.refresh.queue', false);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        RecordSetting::$recorded = [];

        Settings::page(GeneralSettings::class);
    }

    #[Test]
    public function it_reloads_settings_changed_by_another_process_before_the_next_job(): void
    {
        RecordSetting::dispatch('site_name');
        $this->work();

        $this->changeFromAnotherProcess('site_name', 'މާލެ');

        // the worker's settings are still the ones it loaded
        $this->assertEquals('ރާއްޖެ', settings('site_name'));

        RecordSetting::dispatch('site_name');
        $this->work();

        $this->assertEquals(['ރާއްޖެ', 'މާލެ'], RecordSetting::$recorded);
    }

    #[Test]
    public function it_keeps_the_loaded_settings_when_nothing_changed(): void
    {
        RecordSetting::dispatch('site_name');
        $this->work();

        $version = Settings::version();

        RecordSetting::dispatch('site_name');
        $this->work();

        $this->assertEquals($version, Settings::version());
        $this->assertFalse(Settings::refreshIfStale());
    }

    #[Test]
    public function it_keeps_stale_values_when_refreshing_is_off(): void
    {
        RecordSetting::dispatch('site_name');
        $this->work();

        $this->changeFromAnotherProcess('site_name', 'މާލެ');

        RecordSetting::dispatch('site_name');
        $this->work();

        $this->assertEquals(['ރާއްޖެ', 'ރާއްޖެ'], RecordSetting::$recorded);
    }

    #[Test]
    public function it_reloads_before_every_job_without_a_cache(): void
    {
        $this->app['config']->set('settings.cache.enabled', false);

        RecordSetting::dispatch('site_name');
        $this->work();

        $this->changeFromAnotherProcess('site_name', 'މާލެ');

        RecordSetting::dispatch('site_name');
        $this->work();

        $this->assertEquals(['ރާއްޖެ', 'މާލެ'], RecordSetting::$recorded);
    }

    #[Test]
    public function it_reloads_settings_on_octane_requests(): void
    {
        $this->assertEquals('ރާއްޖެ', settings('site_name'));

        $this->changeFromAnotherProcess('site_name', 'މާލެ');

        $this->assertEquals('ރާއްޖެ', settings('site_name'));

        $this->app['events']->dispatch(SettingsServiceProvider::OCTANE_EVENTS[0], [new stdClass]);

        $this->assertEquals('މާލެ', settings('site_name'));
    }

    protected function work(): void
    {
        $this->app['queue.worker']->runNextJob('database', 'default', new WorkerOptions(sleep: 0, maxTries: 1));
    }

    /**
     * Write with a repository of its own, as a web request in another process would
     */
    protected function changeFromAnotherProcess(string $key, mixed $value): void
    {
        $other = $this->app->build(SettingsRepository::class);

        $other->set($key, $value);
    }
}
