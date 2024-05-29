<?php

namespace Javaabu\Settings\Listeners;

use Javaabu\Settings\Events\SettingsUpdated;

class LogSettingsUpdate
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SettingsUpdated  $event
     * @return void
     */
    public function handle(SettingsUpdated $event)
    {
        $attributes = [];
        $old = [];

        foreach ($event->old_settings as $key => $old_value) {
            $new_value = $event->new_settings[$key] ?? null;

            if ($old_value != $new_value) {
                $attributes[$key] = $new_value;
                $old[$key] = $old_value;
            }
        }

        $log = activity()->withProperties(compact('attributes', 'old'));

        if ($user = $event->causer) {
            $log->causedBy($user);
        }

        $log->log('settings');
    }
}
