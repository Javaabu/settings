<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;

class ImportV1SettingsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::page(GeneralSettings::class);
    }

    #[Test]
    public function it_imports_the_v1_table(): void
    {
        Schema::create('persistant_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->text('value');
        });

        $this->app['db']->table('persistant_settings')->insert([
            ['key' => 'site_name', 'value' => 'ދިވެހިރާއްޖެ'],
            ['key' => 'per_page', 'value' => '25'],
            ['key' => 'featured.0', 'value' => 'books'],
            ['key' => 'featured.1', 'value' => 'fruits'],
        ]);

        Settings::set('registration_open', false);

        $this->artisan('settings:import-v1')
            ->expectsOutputToContain('Imported 3 settings.')
            ->assertSuccessful();

        $this->assertEquals('ދިވެހިރާއްޖެ', Settings::get('site_name'));
        $this->assertSame(25, Settings::get('per_page'));
        $this->assertEquals(['books', 'fruits'], Settings::get('featured'));
    }

    #[Test]
    public function it_imports_the_v1_json_file_without_overwriting_unless_forced(): void
    {
        $path = sys_get_temp_dir() . '/javaabu-settings-v1-' . uniqid() . '.json';

        file_put_contents($path, json_encode(['site_name' => 'v1 name', 'per_page' => '30']));

        Settings::set('site_name', 'v2 name');

        $this->artisan('settings:import-v1', ['--from' => 'json', '--path' => $path])
            ->expectsOutputToContain('Skipping `site_name`')
            ->assertSuccessful();

        $this->assertEquals('v2 name', Settings::get('site_name'));
        $this->assertSame(30, Settings::get('per_page'));

        $this->artisan('settings:import-v1', ['--from' => 'json', '--path' => $path, '--force' => true])->assertSuccessful();

        $this->assertEquals('v1 name', Settings::get('site_name'));

        unlink($path);
    }

    #[Test]
    public function it_fails_when_there_is_nothing_to_import(): void
    {
        $this->artisan('settings:import-v1')->assertFailed();
        $this->artisan('settings:import-v1', ['--from' => 'json', '--path' => '/missing.json'])->assertFailed();
        $this->artisan('settings:import-v1', ['--from' => 'redis'])->assertFailed();
    }
}
