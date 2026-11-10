<?php

namespace Javaabu\Settings\Tests\Http;

use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Forms\SettingsForm;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Get;
use PHPUnit\Framework\Attributes\Test;

class LiveFieldsTest extends TestCase
{
    use InteractsWithSettings;

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->settings();
            $router->stencil()->middleware('auth');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Settings::page('storage')->schema(fn () => [
            Setting::make('storage_mode')
                ->default('local')
                ->field(Select::class)
                ->options(['local' => 'Local', 'remote' => 'Remote'])
                ->live(),
            Setting::make('storage_url')
                ->visible(fn (Get $get) => $get('storage_mode') === 'remote')
                ->required(),
        ]);

        $this->setUpSettings();
    }

    #[Test]
    public function it_re_evaluates_settings_forms_on_the_live_route(): void
    {
        $user = $this->user();

        $this->actingAs($user);

        $schema = SettingsForm::forPage('storage')->render('json');

        $this->assertNotNull($schema['live']['token']);
        $this->assertTrue($schema['components'][1]['hidden'] ?? false);

        $this->postJson(route('stencil.evaluate'), [
            '_stencil' => $schema['live']['token'],
            '_stencil_changed' => 'storage_mode',
            'storage_mode' => 'remote',
        ])
            ->assertOk()
            ->assertJsonPath('state.storage_mode', 'remote')
            ->assertJsonPath('schema.components.1.name', 'storage_url')
            ->assertJsonMissingPath('schema.components.1.hidden');
    }

    #[Test]
    public function it_validates_fields_shown_by_closures_only_when_they_are_visible(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/storage', ['storage_mode' => 'local'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch('/settings/storage', ['storage_mode' => 'remote'])
            ->assertSessionHasErrors('storage_url');
    }

    #[Test]
    public function it_refuses_to_re_evaluate_for_users_who_cant_edit_the_page(): void
    {
        $user = $this->user(['view_settings']);

        $this->actingAs($user);

        $token = SettingsForm::forPage('storage')->render('json')['live']['token'];

        $this->postJson(route('stencil.evaluate'), [
            '_stencil' => $token,
            '_stencil_changed' => 'storage_mode',
            'storage_mode' => 'remote',
        ])->assertForbidden();
    }
}
