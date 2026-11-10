<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Listeners\LogSettingsUpdate;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Models\User;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use Javaabu\Settings\Tests\TestSupport\Pages\MailSettings;
use PHPUnit\Framework\Attributes\RequiresFunction;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;

#[RequiresFunction('activity')]
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('settings.log_activity', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Settings::page(GeneralSettings::class);
        Settings::page(MailSettings::class);
    }

    #[Test]
    public function it_logs_the_changed_settings_with_their_old_values(): void
    {
        $user = User::create(['name' => 'އަޙްމަދު', 'email' => 'ahmed@example.com', 'password' => 'secret']);

        Settings::set(['site_name' => 'މާލެ', 'per_page' => 20], causer: $user);

        $log = Activity::query()->latest('id')->first();

        $this->assertEquals('settings', $log->description);
        $this->assertEquals(['site_name' => 'މާލެ'], $log->properties['attributes']);
        $this->assertEquals(['site_name' => 'ރާއްޖެ'], $log->properties['old']);
        $this->assertTrue($log->causer->is($user));
    }

    #[Test]
    public function it_uses_the_authenticated_user_as_the_causer(): void
    {
        $user = User::create(['name' => 'Aisha', 'email' => 'aisha@example.com', 'password' => 'secret']);

        $this->actingAs($user);

        Settings::set('site_name', 'މާލެ');

        $this->assertDatabaseHas('activity_log', ['description' => 'settings', 'causer_type' => 'user', 'causer_id' => $user->id]);
    }

    #[Test]
    public function it_hides_the_values_of_encrypted_settings(): void
    {
        Settings::set('mail_password', 'secret');

        $log = Activity::query()->latest('id')->first();

        $this->assertEquals(['mail_password' => LogSettingsUpdate::HIDDEN], $log->properties['attributes']);
        $this->assertEquals(['mail_password' => LogSettingsUpdate::HIDDEN], $log->properties['old']);
    }

    #[Test]
    public function it_doesnt_log_when_logging_is_off(): void
    {
        $this->app['events']->forget(SettingsUpdated::class);

        Settings::set('site_name', 'މާލެ');

        $this->assertDatabaseCount('activity_log', 0);
    }
}
