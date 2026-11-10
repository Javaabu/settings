---
title: Queue workers and Octane
sidebar_position: 3
---

Queue workers and Octane boot the app once and keep it in memory, so a process that loaded the settings would keep using them after an admin changed them, until it restarted. This package makes them reload changed settings, without a restart.

# How it works

Every change puts a new version in the cache. Before each job, a worker compares the version it loaded with the cache's version, and reloads the settings when they differ (a `JobProcessing` listener, which is what `Queue::before()` registers). Octane does the same before each request, task and tick. The check is one cache read.

```php
// What the service provider does
Event::listen(JobProcessing::class, fn () => Settings::refreshIfStale());
Event::listen([RequestReceived::class, TaskReceived::class, TickReceived::class], fn () => Settings::refreshIfStale());
```

For this to work across processes and servers, use a shared cache store (Redis, Memcached, database; file on a single server). With `cache.enabled` off, workers read the store before every job instead.

A job that runs for a long time reads the settings it had when it started. Call `Settings::refreshIfStale()` yourself between steps if it must see changes sooner.

# Turning it off

```php
// config/settings.php
'refresh' => [
    'queue' => false,   // SETTINGS_REFRESH_QUEUE=false
    'octane' => false,  // SETTINGS_REFRESH_OCTANE=false
],
```

# Testing

The package's own tests start a real worker (`vendor/bin/testbench queue:work`), change a setting from the test process while it runs, and check that its next job sees the new value. In an app, you can test the same thing in-process: run the worker's next job, change the setting the way another process would (with a repository of its own), and run the next job.

```php
use Illuminate\Queue\WorkerOptions;
use Javaabu\Settings\SettingsRepository;

#[Test]
public function workers_see_changed_settings(): void
{
    config(['queue.default' => 'database']);

    SendNewsletter::dispatch();
    $this->app['queue.worker']->runNextJob('database', 'default', new WorkerOptions);

    // another process (a web request) changes the setting
    $this->app->build(SettingsRepository::class)->set('newsletter_from', 'news@example.com');

    SendNewsletter::dispatch();
    $this->app['queue.worker']->runNextJob('database', 'default', new WorkerOptions);

    // assert the second job used news@example.com
}
```
