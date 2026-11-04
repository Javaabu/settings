<?php

namespace Javaabu\Settings\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithSettings {
        defineEnvironment as defineSettingsEnvironment;
    }

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->defineSettingsEnvironment($app);

        $app['config']->set('settings.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSettings();
    }

    #[Test]
    public function it_renders_the_inertia_page_with_the_stencil_schema(): void
    {
        Settings::set('site_name', 'ދިވެހިރާއްޖެ');

        $response = $this->actingAs($this->user(['edit_settings', 'edit_mail_settings']))
            ->get('/settings/general')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings::Settings/Edit')
                ->where('page.key', 'general')
                ->where('page.label', 'General')
                ->where('page.can_update', true)
                ->where('pages.0.active', true)
                ->where('pages.1.key', 'mail')
                ->where('form.id', 'settings-general')
                ->where('form.method', 'PATCH')
                ->where('form.state.site_name', 'ދިވެހިރާއްޖެ')
                ->where('form.state.per_page', 20)
                ->where('form.components.0.name', 'site_name')
                ->where('strings.title', 'Settings')
                ->where('status', null)
                ->etc());

        if (getenv('UPDATE_FIXTURES')) {
            file_put_contents(
                __DIR__ . '/../js/fixtures/edit-props.json',
                json_encode($response->viewData('page')['props'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }
    }

    #[Test]
    public function it_sends_the_status_after_saving(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/general', $this->validInput())
            ->assertRedirect('/settings/general');

        $this->actingAs($user)
            ->get('/settings/general')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('status', 'Settings saved.')
                ->where('form.state.site_name', 'ދިވެހިރާއްޖެ')
                ->etc());
    }

    #[Test]
    public function it_disables_the_form_for_users_who_can_only_view(): void
    {
        $this->actingAs($this->user(['view_settings']))
            ->get('/settings/general')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('page.can_update', false)
                ->where('form.components.0.props.disabled', true)
                ->missing('form.submit')
                ->etc());
    }

    #[Test]
    public function it_translates_the_page_into_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->actingAs($this->user())
            ->get('/settings/general')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('strings.title', __('Settings'))
                ->etc());
    }
}
