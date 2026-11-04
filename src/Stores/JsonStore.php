<?php

namespace Javaabu\Settings\Stores;

use Illuminate\Filesystem\Filesystem;
use Javaabu\Settings\Contracts\SettingsStore;
use Javaabu\Settings\Exceptions\SettingsException;
use stdClass;

/**
 * Keeps values in a JSON file. Writes are atomic: the file is written to a
 * temporary file next to it, then moved.
 */
class JsonStore implements SettingsStore
{
    public function __construct(
        protected Filesystem $files,
        protected string $path,
    ) {}

    public function all(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        $values = json_decode($this->files->get($this->path), true);

        if (! is_array($values)) {
            throw SettingsException::invalidJsonFile($this->path);
        }

        return $values;
    }

    public function put(array $values): void
    {
        $this->write(array_merge($this->all(), $values));
    }

    public function forget(array $keys): void
    {
        $this->write(array_diff_key($this->all(), array_flip($keys)));
    }

    public function flush(): void
    {
        $this->write([]);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function write(array $values): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));

        $json = json_encode($values === [] ? new stdClass : $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->files->replace($this->path, $json);
    }
}
