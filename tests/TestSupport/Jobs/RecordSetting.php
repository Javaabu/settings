<?php

namespace Javaabu\Settings\Tests\TestSupport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Writes the value a setting has inside the job to a file, so a test can see
 * what a queue worker read
 */
class RecordSetting implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** @var array<int, mixed> values recorded by in-process workers */
    public static array $recorded = [];

    public function __construct(
        public string $key,
        public ?string $path = null,
    ) {}

    public function handle(): void
    {
        $value = settings($this->key);

        static::$recorded[] = $value;

        if ($this->path !== null) {
            file_put_contents($this->path, json_encode($value, JSON_UNESCAPED_UNICODE));
        }
    }
}
