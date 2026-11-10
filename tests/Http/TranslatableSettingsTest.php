<?php

namespace Javaabu\Settings\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Forms\SettingsForm;
use Javaabu\Settings\Support\Translatable\SettingsRecord;
use Javaabu\Settings\Support\Translations;
use Javaabu\Settings\Tests\Concerns\InteractsWithTranslatable;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\SiteSettings;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Translatable::class, 'enabled')]
class TranslatableSettingsTest extends TestCase
{
    use InteractsWithSettings {
        defineEnvironment as defineSettingsEnvironment;
    }
    use InteractsWithTranslatable;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->defineSettingsEnvironment($app);

        $app['config']->set('settings.pages', [SiteSettings::class]);
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSettings();
        $this->setUpLanguages(['en', 'dv']);
    }

    #[Test]
    public function it_renders_an_input_per_language(): void
    {
        Settings::set('site_title', ['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ']);

        $this->actingAs($this->user())
            ->get('/settings/site')
            ->assertOk()
            ->assertSee('name="site_title_en"', false)
            ->assertSee('name="site_title_dv"', false)
            ->assertSee('value="ދިވެހިރާއްޖެ"', false)
            ->assertSee('name="footer_text_dv"', false)
            ->assertSee('name="contact_email"', false)
            ->assertDontSee('name="contact_email_dv"', false);
    }

    #[Test]
    public function it_saves_a_value_per_language_and_reads_the_current_one(): void
    {
        $this->actingAs($this->user())
            ->patch('/settings/site', [
                'site_title_en' => 'Maldives',
                'site_title_dv' => 'ދިވެހިރާއްޖެ',
                'footer_text_en' => 'Made in Malé',
                'footer_text_dv' => '',
                'contact_email' => 'hello@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/site');

        $settings = $this->freshSettings();

        $this->assertEquals(['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ'], $settings->stored()['site_title']);
        $this->assertEquals('Maldives', $settings->get('site_title'));
        $this->assertEquals('ދިވެހިރާއްޖެ', $settings->translate('site_title', 'dv'));
        $this->assertEquals('hello@example.com', $settings->get('contact_email'));

        Languages::setCurrentLocale('dv');

        $this->assertEquals('ދިވެހިރާއްޖެ', settings('site_title'));
        $this->assertEquals('Made in Malé', settings('footer_text'), 'falls back to the default language');
        $this->assertNull($settings->translate('footer_text', 'dv', fallback: false));
    }

    #[Test]
    public function it_requires_only_the_default_language(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/site', ['site_title_en' => '', 'site_title_dv' => 'ދިވެހިރާއްޖެ'])
            ->assertSessionHasErrors('site_title_en')
            ->assertSessionDoesntHaveErrors('site_title_dv');

        $this->actingAs($user)
            ->patch('/settings/site', ['site_title_en' => 'Maldives', 'site_title_dv' => ''])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_can_require_every_language(): void
    {
        Settings::page('strict')->schema([
            Setting::make('motto')->required()->translatable(require_all: true),
        ]);

        $this->actingAs($this->user())
            ->patch('/settings/strict', ['motto_en' => 'Hello', 'motto_dv' => ''])
            ->assertSessionHasErrors('motto_dv');
    }

    #[Test]
    public function it_edits_a_record_that_holds_every_language(): void
    {
        Settings::set('site_title', ['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ']);

        $form = SettingsForm::forPage('site');

        $this->assertInstanceOf(SettingsRecord::class, $form->getRecord());
        $this->assertEquals(['site_title', 'footer_text'], $form->getRecord()->getTranslatables());

        $form->assertable()
            ->assertState('site_title_en', 'Maldives')
            ->assertState('site_title_dv', 'ދިވެހިރާއްޖެ');

        $this->assertEquals('ދިވެހިރާއްޖެ', $form->currentValues()['site_title_dv']);
    }

    #[Test]
    public function it_sends_the_inputs_to_the_react_page_and_json_clients(): void
    {
        $this->app['config']->set('settings.views', 'inertia');

        $user = $this->user();

        $this->actingAs($user)
            ->get('/settings/site')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('form.components.0.translatable.inputs.0.name', 'site_title_en')
                ->where('form.components.0.translatable.inputs.1.name', 'site_title_dv')
                ->etc());

        $this->actingAs($user)
            ->getJson('/settings/site')
            ->assertJsonPath('values.site_title', 'Maldives')
            ->assertJsonPath('values.site_title_en', 'Maldives')
            ->assertJsonPath('values.site_title_dv', null);
    }

    #[Test]
    public function it_is_a_plain_setting_when_translations_are_off(): void
    {
        $this->withoutTranslatable(function () {
            $this->assertFalse(Translations::enabled());

            $this->actingAs($this->user())
                ->get('/settings/site')
                ->assertSee('name="site_title"', false)
                ->assertDontSee('name="site_title_dv"', false);

            $this->actingAs($this->user())
                ->patch('/settings/site', ['site_title' => 'Maldives'])
                ->assertSessionHasNoErrors();

            $this->assertEquals('Maldives', $this->freshSettings()->stored()['site_title']);
        });
    }

    #[Test]
    public function it_reads_stored_translations_after_translations_are_turned_off(): void
    {
        Settings::set('site_title', ['en' => 'Maldives', 'dv' => 'ދިވެހިރާއްޖެ']);

        $this->withoutTranslatable(function () {
            $this->app->setLocale('dv');

            $this->assertEquals('ދިވެހިރާއްޖެ', $this->freshSettings()->get('site_title'));

            $this->app->setLocale('fr');

            $this->assertEquals('Maldives', $this->freshSettings()->get('site_title'));
        });
    }
}
