<?php

namespace Javaabu\Settings\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Listeners\LogSettingsUpdate;

class EventServiceProvider extends ServiceProvider
{
    protected function configureEmailVerification(){
        // fix for Registered Event listener getting registered multiple times
        // see https://github.com/laravel/framework/issues/50783#issuecomment-2072411615
    }
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
