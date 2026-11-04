<?php

namespace Javaabu\Settings\Stores;

use Javaabu\Settings\Contracts\SettingsStore;

/**
 * Keeps values in memory, for tests
 */
class ArrayStore implements SettingsStore
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        protected array $values = [],
    ) {}

    public function all(): array
    {
        return $this->values;
    }

    public function put(array $values): void
    {
        $this->values = array_merge($this->values, $values);
    }

    public function forget(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->values[$key]);
        }
    }

    public function flush(): void
    {
        $this->values = [];
    }
}
