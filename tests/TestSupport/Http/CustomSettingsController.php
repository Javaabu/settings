<?php

namespace Javaabu\Settings\Tests\TestSupport\Http;

use Illuminate\Http\Request;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Http\Controllers\SettingsController;

class CustomSettingsController extends SettingsController
{
    /** @var array<int, array<string, mixed>> */
    public static array $saved = [];

    protected function updated(Request $request, SettingsPage $page, array $values): void
    {
        static::$saved[] = $values;
    }
}
