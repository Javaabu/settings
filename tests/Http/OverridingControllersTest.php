<?php

namespace Javaabu\Settings\Tests\Http;

use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Http\CustomSettingsController;
use PHPUnit\Framework\Attributes\Test;

class OverridingControllersTest extends TestCase
{
    use InteractsWithSettings {
        defineEnvironment as defineSettingsEnvironment;
    }

    protected function defineEnvironment($app): void
    {
        $this->defineSettingsEnvironment($app);

        $app['config']->set('settings.controllers.settings', CustomSettingsController::class);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->settings();
            $router->settings(['prefix' => 'other', 'name' => 'other', 'controller' => CustomSettingsController::class, 'middleware' => ['auth']]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        CustomSettingsController::$saved = [];

        $this->setUpSettings();
    }

    #[Test]
    public function it_uses_the_controller_from_config(): void
    {
        $this->actingAs($this->user())
            ->patch('/settings/general', $this->validInput())
            ->assertRedirect('/settings/general');

        $this->assertCount(1, CustomSettingsController::$saved);
        $this->assertEquals('ދިވެހިރާއްޖެ', CustomSettingsController::$saved[0]['site_name']);
    }

    #[Test]
    public function it_uses_the_controller_passed_to_the_macro_with_its_own_prefix_and_names(): void
    {
        $this->actingAs($this->user())
            ->patch('/other/general', $this->validInput())
            ->assertRedirect('/other/general');

        $this->assertCount(1, CustomSettingsController::$saved);
        $this->assertEquals(url('/other/general'), route('other.edit', 'general'));
    }
}
