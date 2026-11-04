<?php

namespace Javaabu\Settings\Listeners;

use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Facades\Settings;

/**
 * Logs changed settings to spatie/laravel-activitylog, with the old and new
 * values. Registered only when the package is installed. Encrypted settings
 * are logged as changed, without their values.
 */
class LogSettingsUpdate
{
    public const string HIDDEN = '********';

    public function handle(SettingsUpdated $event): void
    {
        $attributes = $event->changes();

        if ($attributes === []) {
            return;
        }

        $old = [];

        foreach (array_keys($attributes) as $key) {
            $old[$key] = $event->old_settings[$key] ?? null;

            if (Settings::definition($key)?->isEncrypted()) {
                $attributes[$key] = self::HIDDEN;
                $old[$key] = self::HIDDEN;
            }
        }

        $log = activity()->withProperties(compact('attributes', 'old'));

        if ($event->causer) {
            $log->causedBy($event->causer);
        }

        $log->log('settings');
    }
}
