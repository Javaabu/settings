<?php

namespace Javaabu\Settings\Tests;

use Javaabu\Forms\FormsServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Permissions\Models\Permission;
use Javaabu\Permissions\Models\Role;
use Javaabu\Permissions\PermissionsServiceProvider;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\SettingsRegistry;
use Javaabu\Settings\SettingsServiceProvider;
use Javaabu\Settings\Tests\TestSupport\Models\Admin;
use Javaabu\Settings\Tests\TestSupport\Models\User;
use Javaabu\Settings\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Stencil\StencilServiceProvider;
use Javaabu\Translatable\TranslatableServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('settings.store', 'array');
        $app['config']->set('settings.log_activity', false);
        $app['config']->set('permissions.guards', ['web', 'web_admin']);

        $app['config']->set('auth.guards', [
            'web' => ['driver' => 'session', 'provider' => 'users'],
            'web_admin' => ['driver' => 'session', 'provider' => 'admins'],
        ]);

        $app['config']->set('auth.providers', [
            'users' => ['driver' => 'eloquent', 'model' => User::class],
            'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            StencilServiceProvider::class,
            SettingsServiceProvider::class,
            TestServiceProvider::class,
        ];

        if (class_exists(ActivitylogServiceProvider::class)) {
            $providers[] = ActivitylogServiceProvider::class;
        }

        if (class_exists(TranslatableServiceProvider::class)) {
            $providers[] = TranslatableServiceProvider::class;
        }

        return $providers;
    }

    protected function tearDown(): void
    {
        Settings::clearResolvedInstances();

        parent::tearDown();
    }

    protected function registry(): SettingsRegistry
    {
        return $this->app->make(SettingsRegistry::class);
    }
}
