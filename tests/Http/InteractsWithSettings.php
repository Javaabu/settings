<?php

namespace Javaabu\Settings\Tests\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\SettingsRepository;
use Javaabu\Settings\Tests\TestSupport\Models\Admin;
use Javaabu\Settings\Tests\TestSupport\Models\User;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use Javaabu\Settings\Tests\TestSupport\Pages\MailSettings;

trait InteractsWithSettings
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('settings.store', 'database');
        $app['config']->set('settings.pages', [GeneralSettings::class, MailSettings::class]);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->settings();
            $router->settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
        });
    }

    protected function setUpSettings(): void
    {
        $this->artisan('permissions:sync');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function user(array $permissions = ['edit_settings']): User
    {
        $user = User::create([
            'name' => 'އަޙްމަދު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function admin(array $permissions = ['edit_settings']): Admin
    {
        $admin = Admin::create([
            'name' => 'Aisha',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validInput(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'ދިވެހިރާއްޖެ',
            'per_page' => '25',
            'registration_open' => '1',
            'theme' => 'dark',
            'seo_description' => 'ރާއްޖޭގެ ވެބްސައިޓް',
            'seo_keywords' => ['dhivehi'],
        ], $overrides);
    }

    protected function freshSettings(): SettingsRepository
    {
        return Settings::getFacadeRoot()->refresh();
    }
}
