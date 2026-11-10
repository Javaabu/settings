<?php

namespace Javaabu\Settings\Tests\Http;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Javaabu\Permissions\Permissions;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Policies\DenyAllPolicy;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends TestCase
{
    use InteractsWithSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSettings();
    }

    #[Test]
    public function it_registers_the_permissions_and_the_permissions_of_pages(): void
    {
        $this->assertTrue(Permissions::has('view_settings'));
        $this->assertTrue(Permissions::has('edit_settings'));
        $this->assertTrue(Permissions::has('edit_mail_settings'));
        $this->assertEquals('Settings', Permissions::label('settings'));
        $this->assertDatabaseHas('permissions', ['name' => 'edit_settings', 'guard_name' => 'web_admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'edit_mail_settings', 'guard_name' => 'web']);
    }

    #[Test]
    public function it_rejects_guests(): void
    {
        $this->getJson('/settings')->assertUnauthorized();
        $this->getJson('/settings/general')->assertUnauthorized();
        $this->patchJson('/settings/general', $this->validInput())->assertUnauthorized();
    }

    #[Test]
    public function it_forbids_users_without_a_settings_permission(): void
    {
        $user = $this->user([]);

        $this->actingAs($user)->get('/settings')->assertForbidden();
        $this->actingAs($user)->get('/settings/general')->assertForbidden();
        $this->actingAs($user)->patch('/settings/general', $this->validInput())->assertForbidden();

        $this->assertFalse($this->freshSettings()->has('site_name'));
    }

    #[Test]
    public function it_lets_users_who_can_view_see_a_disabled_form_but_not_save(): void
    {
        $user = $this->user(['view_settings']);

        $this->actingAs($user)
            ->get('/settings/general')
            ->assertOk()
            ->assertSee('You can view these settings but not change them.')
            ->assertDontSee('Save settings')
            ->assertSee('disabled', false);

        $this->actingAs($user)->patch('/settings/general', $this->validInput())->assertForbidden();
    }

    #[Test]
    public function it_needs_the_permission_of_a_page_that_has_its_own(): void
    {
        $editor = $this->user(['edit_settings']);

        $this->actingAs($editor)->get('/settings/mail')->assertForbidden();
        $this->actingAs($editor)->patch('/settings/mail', ['mail_from_address' => 'a@example.com'])->assertForbidden();

        $mailer = $this->user(['edit_mail_settings']);

        $this->actingAs($mailer)->get('/settings/general')->assertForbidden();
        $this->actingAs($mailer)
            ->patch('/settings/mail', ['mail_from_address' => 'info@javaabu.com'])
            ->assertRedirect('/settings/mail');

        $this->assertEquals('info@javaabu.com', $this->freshSettings()->get('mail_from_address'));
    }

    #[Test]
    public function it_lists_only_the_pages_a_user_can_see(): void
    {
        $this->actingAs($this->user(['edit_mail_settings']))
            ->getJson('/settings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'mail');
    }

    #[Test]
    public function it_works_for_another_guard_and_user_model(): void
    {
        $admin = $this->admin(['edit_settings']);

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/settings')
            ->assertRedirect('/admin/settings/general');

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/settings/general')
            ->assertOk()
            ->assertSee('/admin/settings/general', false);

        $this->actingAs($admin, 'web_admin')
            ->patch('/admin/settings/general', $this->validInput())
            ->assertRedirect('/admin/settings/general');

        $this->assertEquals('ދިވެހިރާއްޖެ', $this->freshSettings()->get('site_name'));
    }

    #[Test]
    public function it_doesnt_let_a_user_of_one_guard_into_the_routes_of_another(): void
    {
        $this->actingAs($this->user(['edit_settings']))
            ->getJson('/admin/settings/general')
            ->assertUnauthorized();
    }

    #[Test]
    public function it_doesnt_let_an_admin_into_the_routes_of_users(): void
    {
        $this->actingAs($this->admin(['edit_settings']), 'web_admin');

        Auth::shouldUse('web');

        $this->getJson('/settings/general')->assertUnauthorized();
    }

    #[Test]
    public function the_app_can_override_the_policy(): void
    {
        Gate::policy(SettingsPage::class, DenyAllPolicy::class);

        $this->actingAs($this->user(['edit_settings']))->get('/settings/general')->assertForbidden();
    }

    #[Test]
    public function pages_can_hide_themselves(): void
    {
        Settings::page('hidden')->schema([])->permission('edit_settings');

        $page = new class extends SettingsPage
        {
            protected ?string $key = 'secret';

            public function definition(): array
            {
                return [];
            }

            public function isVisible(): bool
            {
                return false;
            }
        };

        Settings::page($page);

        $this->actingAs($this->user(['edit_settings']))->get('/settings/secret')->assertNotFound();
        $this->actingAs($this->user(['edit_settings']))->get('/settings/hidden')->assertOk();
    }
}
