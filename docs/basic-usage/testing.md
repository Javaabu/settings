---
title: Testing
sidebar_position: 4
---

# Faking settings

`Settings::fake()` keeps values in memory for the rest of the test: nothing is read from or written to the store or the cache.

```php
use Javaabu\Settings\Facades\Settings;

#[Test]
public function it_closes_registration(): void
{
    Settings::fake(['registration_open' => false]);

    $this->get('/register')->assertForbidden();
}
```

Defaults still come from the definitions. Fake a default with `Settings::fakeDefault('per_page', 5)`, and go back to the real store with `Settings::stopFaking()`.

# The FakesSettings trait

Kept from v1, for tests that use it:

```php
use Javaabu\Settings\Testing\FakesSettings;

class InvoicesTest extends TestCase
{
    use FakesSettings;

    #[Test]
    public function it_charges_vat(): void
    {
        $this->setFakeSetting('vat_rate', 8);              // the value; its default becomes null
        $this->setFakeSetting('currency', 'MVR', 'USD');    // the value and its default
        $this->setFakeDefaultSetting('per_page', 5);        // only the default
        $this->setFakeSettings(['site_name' => 'ދިވެހިރާއްޖެ']); // several values

        // ...
    }
}
```

The trait is a thin layer over `Settings::fake()` and `Settings::fakeDefault()`.

# Testing pages

Test settings pages through their routes, as users use them:

```php
#[Test]
public function admins_can_change_the_site_name(): void
{
    $admin = User::factory()->create();
    $admin->givePermissionTo('edit_settings');

    $this->actingAs($admin)
        ->patch(route('settings.update', 'general'), ['site_name' => 'ދިވެހިރާއްޖެ', 'per_page' => 20])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.edit', 'general'));

    $this->assertEquals('ދިވެހިރާއްޖެ', settings('site_name'));
}
```

Or test a page's form without HTTP, with stencil's `AssertableForm`:

```php
use Javaabu\Settings\Forms\SettingsForm;

SettingsForm::forPage('general')
    ->assertable()
    ->assertHasField('site_name')
    ->assertState('site_name', settings('site_name'))
    ->assertFieldRequired('site_name')
    ->assertInvalid(['site_name' => ''], 'site_name');
```

Long-running processes: [Queue workers and Octane](../advanced-usage/queue-workers-and-octane.md#testing) shows how to test a worker across a change.
