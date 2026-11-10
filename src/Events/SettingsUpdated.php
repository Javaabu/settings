<?php

namespace Javaabu\Settings\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Settings changed. Holds the values of the changed keys before and after.
 */
class SettingsUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $old_settings
     * @param  array<string, mixed>  $new_settings
     */
    public function __construct(
        public array $old_settings,
        public array $new_settings,
        public ?Model $causer = null,
    ) {}

    /**
     * The keys whose value changed, with their new values
     *
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $changes = [];

        foreach ($this->new_settings as $key => $value) {
            if (($this->old_settings[$key] ?? null) != $value) {
                $changes[$key] = $value;
            }
        }

        foreach ($this->old_settings as $key => $value) {
            if (! array_key_exists($key, $this->new_settings) && $value !== null) {
                $changes[$key] = null;
            }
        }

        return $changes;
    }

    public function changed(string $key): bool
    {
        return array_key_exists($key, $this->changes());
    }
}
