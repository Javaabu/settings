<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Javaabu\Settings\Events\SettingsUpdated;
use Javaabu\Settings\Tests\TestCase;

class SettingsUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_that_settings_have_been_updated()
    {
        event(new SettingsUpdated(
            ['name' => 'foo', 'title' => 'bar'],
            ['name' => 'bar', 'title' => 'bar'],
        ));

        /** @var Activity $log */
        $log = Activity::latest('id')->first();
        $changes = $log->changes();

        $old = $changes->get('old');
        $new = $changes->get('attributes');

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'description' => 'settings',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
        ]);

        $this->assertArrayHasKey('name', $new);
        $this->assertEquals('bar', $new['name']);

        $this->assertArrayHasKey('name', $old);
        $this->assertEquals('foo', $old['name']);

        $this->assertArrayNotHasKey('title', $new);
        $this->assertArrayNotHasKey('title', $old);
    }
}
