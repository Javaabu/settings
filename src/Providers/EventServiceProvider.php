<?php

namespace Javaabu\Settings\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Listeners\LogSettingsUpdate;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SettingsUpdated::class => [
            LogSettingsUpdate::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
