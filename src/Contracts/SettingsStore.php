<?php

namespace Javaabu\Settings\Contracts;

/**
 * Where setting values are kept. Values are keyed by setting key and can be any
 * JSON-serializable value.
 */
interface SettingsStore
{
    /**
     * Every stored value, keyed by setting key
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Store the values, replacing the ones with the same keys
     *
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void;

    /**
     * Remove the values of the keys
     *
     * @param  array<int, string>  $keys
     */
    public function forget(array $keys): void;

    /**
     * Remove every value
     */
    public function flush(): void;
}
