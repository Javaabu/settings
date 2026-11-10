<?php

namespace Javaabu\Settings\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Javaabu\Settings\SettingsRepository;

/**
 * Copies the values settings v1 kept with anlutro/l4-settings (a table or a
 * JSON file) into the v2 store
 */
class ImportV1SettingsCommand extends Command
{
    protected $signature = 'settings:import-v1
        {--from=database : Where v1 kept the values: database or json}
        {--connection= : The database connection of the v1 table (database)}
        {--table=persistant_settings : The v1 table (database)}
        {--path= : The v1 JSON file, storage/settings.json by default (json)}
        {--force : Overwrite settings that already have a value in v2}';

    protected $description = 'Import the values of settings v1 (anlutro/l4-settings) into the settings store';

    public function handle(SettingsRepository $settings, Filesystem $files): int
    {
        $values = match ($this->option('from')) {
            'database' => $this->fromDatabase(),
            'json' => $this->fromJson($files),
            default => null,
        };

        if ($values === null) {
            $this->error('Nothing to import: check --from, --table and --path.');

            return self::FAILURE;
        }

        $import = [];

        foreach ($values as $key => $value) {
            if ($settings->has($key) && ! $this->option('force')) {
                $this->line("Skipping `{$key}`: it already has a value (use --force to overwrite).");

                continue;
            }

            $this->line("Importing `{$key}`...");

            $definition = $settings->definition($key);

            $import[$key] = $definition ? $definition->castValue($value) : $value;
        }

        $settings->set($import);

        $count = count($import);

        $this->info("Imported {$count} settings.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fromDatabase(): ?array
    {
        $connection = $this->laravel['db']->connection($this->option('connection'));
        $table = (string) $this->option('table');

        if (! $connection->getSchemaBuilder()->hasTable($table)) {
            return null;
        }

        $values = [];

        foreach ($connection->table($table)->get(['key', 'value']) as $row) {
            Arr::set($values, $row->key, $row->value);
        }

        return $values;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fromJson(Filesystem $files): ?array
    {
        $path = $this->option('path') ?: storage_path('settings.json');

        if (! $files->exists($path)) {
            return null;
        }

        $values = json_decode($files->get($path), true);

        return is_array($values) ? $values : null;
    }
}
