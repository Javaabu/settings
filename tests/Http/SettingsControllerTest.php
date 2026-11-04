<?php

namespace Javaabu\Settings\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Enums\Themes;
use PHPUnit\Framework\Attributes\Test;

class SettingsControllerTest extends TestCase
{
    use InteractsWithSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSettings();
    }

    #[Test]
    public function it_redirects_the_index_to_the_first_page(): void
    {
        $this->actingAs($this->user())
            ->get('/settings')
            ->assertRedirect('/settings/general');
    }

    #[Test]
    public function it_redirects_to_the_first_page_the_user_can_see(): void
    {
        $this->actingAs($this->user(['edit_mail_settings']))
            ->get('/settings')
            ->assertRedirect('/settings/mail');
    }

    #[Test]
    public function it_shows_the_form_of_a_page_with_the_current_values(): void
    {
        Settings::set('site_name', 'ދިވެހިރާއްޖެ');

        $this->actingAs($this->user(['edit_settings', 'edit_mail_settings']))
            ->get('/settings/general')
            ->assertOk()
            ->assertSee('data-settings-page="general"', false)
            ->assertSee('ދިވެހިރާއްޖެ')
            ->assertSee('Site name')
            ->assertSee('Search engines')
            ->assertSee('/settings/mail')
            ->assertSee('Save settings')
            ->assertSee('name="per_page"', false)
            ->assertSee('value="20"', false);
    }

    #[Test]
    public function it_saves_a_page(): void
    {
        $this->actingAs($this->user())
            ->patch('/settings/general', $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/general')
            ->assertSessionHas('status', 'Settings saved.');

        $this->app->setLocale('dv');

        $this->actingAs($this->user())
            ->patch('/settings/general', $this->validInput())
            ->assertSessionHas('status', 'ސެޓިންގްސް ސޭވްކުރެވިއްޖެ.');

        $settings = $this->freshSettings();

        $this->assertEquals('ދިވެހިރާއްޖެ', $settings->get('site_name'));
        $this->assertSame(25, $settings->get('per_page'));
        $this->assertTrue($settings->get('registration_open'));
        $this->assertSame(Themes::DARK, $settings->get('theme'));
        $this->assertEquals('ރާއްޖޭގެ ވެބްސައިޓް', $settings->get('seo_description'));
        $this->assertEquals(['dhivehi'], $settings->get('seo_keywords'));

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => '"ދިވެހިރާއްޖެ"']);
    }

    #[Test]
    public function it_saves_an_unchecked_checkbox_as_false(): void
    {
        $input = $this->validInput();
        unset($input['registration_open']);

        $this->actingAs($this->user())->patch('/settings/general', $input)->assertSessionHasNoErrors();

        $this->assertFalse($this->freshSettings()->get('registration_open'));
    }

    #[Test]
    public function it_validates_with_the_rules_of_the_fields(): void
    {
        $this->actingAs($this->user())
            ->from('/settings/general')
            ->patch('/settings/general', $this->validInput([
                'site_name' => '',
                'per_page' => '500',
                'theme' => 'purple',
                'seo_keywords' => ['php'],
            ]))
            ->assertRedirect('/settings/general')
            ->assertSessionHasErrors(['site_name', 'per_page', 'theme', 'seo_keywords.0']);

        $this->assertFalse($this->freshSettings()->has('site_name'));
    }

    #[Test]
    public function it_ignores_input_that_isnt_on_the_page(): void
    {
        $this->actingAs($this->user())
            ->patch('/settings/general', $this->validInput(['mail_from_address' => 'hacker@example.com', 'other' => 'x']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->freshSettings()->has('mail_from_address'));
        $this->assertFalse($this->freshSettings()->has('other'));
    }

    #[Test]
    public function it_uploads_file_settings_and_deletes_the_file_they_replace(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/general', $this->validInput(['logo' => UploadedFile::fake()->image('logo.png')]))
            ->assertSessionHasNoErrors();

        $first = $this->freshSettings()->get('logo');

        $this->assertStringStartsWith('settings/', $first);
        Storage::disk('public')->assertExists($first);
        $this->assertEquals(Storage::disk('public')->url($first), $this->freshSettings()->url('logo'));

        $this->actingAs($user)
            ->patch('/settings/general', $this->validInput(['logo' => UploadedFile::fake()->image('new-logo.png')]))
            ->assertSessionHasNoErrors();

        $second = $this->freshSettings()->get('logo');

        $this->assertNotEquals($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($user)->patch('/settings/general', $this->validInput())->assertSessionHasNoErrors();

        $this->assertEquals($second, $this->freshSettings()->get('logo'));
    }

    #[Test]
    public function it_never_sends_encrypted_values_and_keeps_them_when_left_empty(): void
    {
        Settings::set('mail_password', 'super-secret');

        $user = $this->user(['edit_mail_settings']);

        $this->actingAs($user)
            ->get('/settings/mail')
            ->assertOk()
            ->assertDontSee('super-secret')
            ->assertSee('Leave blank to keep the current value.');

        $this->actingAs($user)
            ->patch('/settings/mail', ['mail_from_address' => 'info@javaabu.com', 'mail_password' => ''])
            ->assertSessionHasNoErrors();

        $this->assertEquals('super-secret', $this->freshSettings()->get('mail_password'));
        $this->assertEquals('info@javaabu.com', $this->freshSettings()->get('mail_from_address'));

        $this->actingAs($user)
            ->patch('/settings/mail', ['mail_from_address' => 'info@javaabu.com', 'mail_password' => 'new-secret'])
            ->assertSessionHasNoErrors();

        $this->assertEquals('new-secret', $this->freshSettings()->get('mail_password'));
    }

    #[Test]
    public function it_returns_404_for_an_unknown_page(): void
    {
        $this->actingAs($this->user())->get('/settings/missing')->assertNotFound();
        $this->actingAs($this->user())->patch('/settings/missing')->assertNotFound();
    }

    #[Test]
    public function it_shows_the_pages_in_the_users_locale(): void
    {
        $this->app->setLocale('dv');

        $this->actingAs($this->user())
            ->get('/settings/general')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('<h1 class="text-2xl font-semibold">ސެޓިންގްސް</h1>', false)
            ->assertSee('ސެޓިންގްސް ސޭވްކުރޭ');
    }

    #[Test]
    public function it_returns_json_to_json_requests(): void
    {
        $user = $this->user(['edit_settings']);

        $this->actingAs($user)
            ->getJson('/settings')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'general')
            ->assertJsonPath('data.0.can_update', true)
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)
            ->getJson('/settings/general')
            ->assertOk()
            ->assertJsonPath('page.label', 'General')
            ->assertJsonPath('values.site_name', 'ރާއްޖެ')
            ->assertJsonPath('values.per_page', 20);

        $this->actingAs($user)
            ->patchJson('/settings/general', $this->validInput())
            ->assertOk()
            ->assertJsonPath('message', 'Settings saved.')
            ->assertJsonPath('values.site_name', 'ދިވެހިރާއްޖެ');

        $this->actingAs($user)
            ->patchJson('/settings/general', $this->validInput(['site_name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('site_name');
    }
}
