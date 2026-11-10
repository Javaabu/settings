<?php

namespace Javaabu\Settings\Stores;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use Javaabu\Settings\Contracts\SettingsStore;

/**
 * Keeps values in a table with a row per setting: key, and the value as JSON
 */
class DatabaseStore implements SettingsStore
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table = 'settings',
    ) {}

    public function all(): array
    {
        return $this->query()
            ->pluck('value', 'key')
            ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
            ->all();
    }

    public function put(array $values): void
    {
        if ($values === []) {
            return;
        }

        $now = Date::now();

        $rows = [];

        foreach ($values as $key => $value) {
            $rows[] = [
                'key' => (string) $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->query()->upsert($rows, ['key'], ['value', 'updated_at']);
    }

    public function forget(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $this->query()->whereIn('key', $keys)->delete();
    }

    public function flush(): void
    {
        $this->query()->delete();
    }

    public function table(): string
    {
        return $this->table;
    }

    protected function query(): Builder
    {
        return $this->connection->table($this->table);
    }
}
